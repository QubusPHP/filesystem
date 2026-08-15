<?php

/**
 * Qubus\FileSystem
 *
 * @link       https://github.com/QubusPHP/filesystem
 * @copyright  2021
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\FileSystem;

use League\Flysystem\Filesystem as LeagueFileSystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use League\Flysystem\WhitespacePathNormalizer;
use Qubus\Exception\Exception;
use Qubus\Exception\Http\Client\NotFoundException;
use Qubus\Exception\IO\FileSystem\DirectoryNotWritableException;
use InvalidArgumentException;

use function array_values;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function dirname;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fopen;
use function flock;
use function function_exists;
use function fseek;
use function ftruncate;
use function fwrite;
use function is_dir;
use function is_file;
use function is_link;
use function is_readable;
use function is_resource;
use function mkdir;
use function realpath;
use function rmdir;
use function rtrim;
use function scandir;
use function sprintf;
use function stream_context_create;
use function stream_get_contents;
use function str_contains;
use function strlen;
use function substr;
use function trim;
use function unlink;

use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const DIRECTORY_SEPARATOR;
use const LOCK_UN;
use const LOCK_EX;
use const SEEK_END;
use const SEEK_SET;

final class FileSystem extends LeagueFileSystem
{
    private PathNormalizer $pathNormalizer;

    /**
     * @param array<string, mixed> $configArray
     */
    public function __construct(
        FilesystemAdapter $adapter,
        array $configArray = [],
        ?PathNormalizer $pathNormalizer = null
    ) {
        $this->pathNormalizer = $pathNormalizer ?? new WhitespacePathNormalizer();

        parent::__construct($adapter, $configArray, $this->pathNormalizer);
    }

    /**
     * Custom function to use curl, fopen, or use file_get_contents
     * if curl is not available.
     *
     * @param string $filename Resource to read.
     * @param bool $useIncludePath Whether to use include path.
     * @param bool $context Whether to use a context resource.
     * @return string|bool
     */
    public function getContents(string $filename, bool $useIncludePath = false, bool $context = true): string|bool
    {
        $opts = [
            'http' => [
                'timeout' => 360.0,
            ],
        ];

        if ($context === true) {
            $context = stream_context_create($opts);
        } else {
            $context = null;
        }

        $isStream = str_contains($filename, '://');
        if (! $isStream && ! $useIncludePath && ! file_exists($filename)) {
            return false;
        }

        $result = @file_get_contents($filename, $useIncludePath, $context);

        if ($result !== false) {
            return $result;
        }

        $handle = @fopen($filename, 'r', $useIncludePath, $context);
        if (is_resource($handle)) {
            $contents = stream_get_contents($handle);
            fclose($handle);

            if ($contents !== false) {
                return $contents;
            }
        }

        if (! $isStream || ! function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init();
        if ($ch === false) {
            return false;
        }

        curl_setopt($ch, CURLOPT_URL, $filename);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 360);
        curl_setopt($ch, CURLOPT_TIMEOUT, 360);
        $output = curl_exec($ch);

        return $output;
    }

    /**
     * Write the contents of a file.
     *
     * @param string $path
     * @param string $contents
     * @param bool   $lock
     * @return int|bool
     */
    public function putContents(string $path, string $contents, bool $lock = false): int|bool
    {
        return file_put_contents(filename: $path, data: $contents, flags: $lock ? LOCK_EX : 0);
    }

    /**
     * Custom make directory function.
     *
     * This function will check if the path is an existing directory,
     * if not, then it will be created with set permissions and also created
     * recursively if needed.
     *
     * @param string $path Path to be created.
     * @param int $permissions Permission to set for directory.
     * @param bool $recursive Whether to allow the creation of nested directories.
     * @return bool True if the directory was created.
     * @throws DirectoryNotWritableException If path is not writable, or lacks permission to mkdir.
     * @throws Exception If path is invalid.
     */
    public function mkdir(string $path, int $permissions = 0755, bool $recursive = true): bool
    {
        if ('' === trim($path)) {
            throw new Exception('Invalid directory path: Empty path given.');
        }

        if (! is_dir($path)) {
            if (! @mkdir($path, $permissions, $recursive)) {
                throw new DirectoryNotWritableException(
                    sprintf(
                        'The following directory could not be created: %s',
                        $path
                    )
                );
            }
        }

        return true;
    }

    /**
     * Removes directory recursively along with any files.
     *
     * @param string $dir Directory that should be removed.
     * @throws InvalidArgumentException If the path resolves to a filesystem root.
     */
    public function rmdir(string $dir): void
    {
        if (is_link($dir)) {
            unlink($dir);
            return;
        }

        $resolvedPath = realpath($dir);
        if ($resolvedPath !== false && dirname($resolvedPath) === $resolvedPath) {
            throw new InvalidArgumentException(sprintf('Refusing to remove filesystem root "%s".', $dir));
        }

        if (is_dir($dir)) {
            $objects = scandir($dir);
            if ($objects === false) {
                return;
            }

            foreach ($objects as $object) {
                if ($object !== "." && $object !== "..") {
                    $path = $dir . DIRECTORY_SEPARATOR . $object;
                    if (is_link($path)) {
                        unlink($path);
                    } elseif (is_dir($path)) {
                        $this->rmdir($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Checks whether a file or directory exists.
     *
     * @param string $filename  Path to the file or directory.
     * @param bool $throw       Determines whether to do a simple check or throw an exception.
     *                          Default: true.
     * @return bool             True if the file or directory specified by $filename exists;
     *                          false otherwise if $throw is set to false.
     * @throws NotFoundException If file does not exist.
     */
    public function exists(string $filename, bool $throw = true): bool
    {
        if (! file_exists($filename)) {
            if ($throw === true) {
                throw new NotFoundException(sprintf('"%s" does not exist.', $filename));
            }
            return false;
        }
        return true;
    }

    /**
     * Get an array that represents the directory tree.
     *
     * @param string $dir Directory path.
     * @param string $include Include sub directories. Default: dirs. Option: files.
     * @return list<string>
     * @throws NotFoundException
     */
    public function directoryListing(string $dir, string $include = 'dirs'): array
    {
        if ($include !== 'files' && $include !== 'dirs') {
            throw new InvalidArgumentException(
                sprintf('Invalid directory listing type "%s"; expected "dirs" or "files".', $include)
            );
        }

        $entries = is_dir($dir) && is_readable($dir) ? scandir($dir) : false;
        if ($entries === false) {
            throw new NotFoundException(sprintf('Directory "%s" could not be read.', $dir));
        }

        $basePath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
        foreach ($entries as $key => $entry) {
            if (
                $entry === '.'
                || $entry === '..'
                || ($include === 'files' && is_dir($basePath . $entry))
                || ($include === 'dirs' && ! is_dir($basePath . $entry))
            ) {
                unset($entries[$key]);
            }
        }

        return array_values($entries);
    }

    /**
     * Normalize a filesystem path.
     *
     * @param string $path Path to normalize.
     * @return string Normalized path.
     */
    public function normalizePath(string $path): string
    {
        return $this->pathNormalizer->normalizePath($path);
    }

    /**
     * Removes trailing forward slashes and backslashes if they exist.
     *
     * The primary use of this is for paths and thus should be used for paths. It is
     * not restricted to paths and offers no specific path support.
     *
     * @param string $string What to remove the trailing slashes from.
     * @return string String without the trailing slashes.
     */
    public function removeTrailingSlash(string $string): string
    {
        return rtrim($string, '/\\');
    }

    /**
     * Appends a trailing slash.
     *
     * Will remove trailing forward and backslashes if it exists already before adding
     * a trailing forward slash. This prevents double slashing a string or path.
     *
     * The primary use of this is for paths and thus should be used for paths. It is
     * not restricted to paths and offers no specific path support.
     *
     * @param string $string What to add the trailing slash to.
     * @return string String with trailing slash added.
     */
    public function addTrailingSlash(string $string): string
    {
        return $this->removeTrailingSlash($string) . '/';
    }

    /**
     * Prepends data to a file.
     *
     * @param string $path
     * @param string $data
     * @return bool
     */
    public function prepend(string $path, string $data): bool
    {
        return $this->writeToExistingFile($path, $data, true, false);
    }

    /**
     * Appends data to a file.
     *
     * @param string $path
     * @param string $data
     * @return bool
     */
    public function append(string $path, string $data): bool
    {
        return $this->writeToExistingFile($path, $data, false, true);
    }

    /**
     * Updates a file.
     *
     * @param string $path
     * @param string $data
     * @return bool
     */
    public function update(string $path, string $data): bool
    {
        return $this->writeToExistingFile($path, $data);
    }

    private function writeToExistingFile(
        string $path,
        string $data,
        bool $prepend = false,
        bool $append = false
    ): bool {
        if (! is_file($path)) {
            return false;
        }

        $handle = @fopen($path, $prepend ? 'r+b' : 'cb');
        if (! is_resource($handle) || ! flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            return false;
        }

        if ($prepend) {
            $contents = stream_get_contents($handle);
            if ($contents === false) {
                flock($handle, LOCK_UN);
                fclose($handle);
                return false;
            }

            $data .= $contents;
        }

        $positioned = $append ? fseek($handle, 0, SEEK_END) : fseek($handle, 0, SEEK_SET);
        if ($positioned !== 0 || (! $append && ! ftruncate($handle, 0))) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return false;
        }

        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $bytes = fwrite($handle, substr($data, $written));
            if ($bytes === false || $bytes === 0) {
                flock($handle, LOCK_UN);
                fclose($handle);
                return false;
            }

            $written += $bytes;
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        return true;
    }
}
