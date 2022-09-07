<?php

/**
 * Qubus\FileSystem
 *
 * @link       https://github.com/QubusPHP/filesystem
 * @copyright  2021 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\FileSystem;

use League\Flysystem\Filesystem as LeagueFileSystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use Qubus\Exception\Exception;
use Qubus\Exception\Http\Client\NotFoundException;
use Qubus\Exception\IO\FileSystem\DirectoryNotWritableException;

use function array_values;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fopen;
use function function_exists;
use function is_dir;
use function mkdir;
use function rmdir;
use function rtrim;
use function scandir;
use function sprintf;
use function stream_context_create;
use function stream_get_contents;
use function trim;
use function unlink;

use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use const DIRECTORY_SEPARATOR;
use const FILE_APPEND;
use const LOCK_EX;

final class FileSystem extends LeagueFileSystem
{
    /** @var FilesystemAdapter */
    private FilesystemAdapter $adapter;

    /** @var array */
    private array $config;

    /** @var ?PathNormalizer */
    private ?PathNormalizer $pathNormalizer = null;

    public function __construct(
        FilesystemAdapter $adapter,
        array $config = [],
        ?PathNormalizer $pathNormalizer = null
    ) {
        parent::__construct($adapter, $config, $pathNormalizer);
    }

    /**
     * Custom function to use curl, fopen, or use file_get_contents
     * if curl is not available.
     *
     * @param string $filename Resource to read.
     * @param bool $useIncludePath Whether or not to use include path.
     * @param bool $context Whether or not to use a context resource.
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

        $result = file_get_contents($filename, $useIncludePath, $context);

        if ($result) {
            return $result;
        } else {
            $handle = fopen($filename, "r", $useIncludePath, $context);
            $contents = stream_get_contents($handle);
            fclose($handle);
            if ($contents) {
                return $contents;
            } elseif (! function_exists('curl_init')) {
                return false;
            } else {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $filename);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 360);
                $output = curl_exec($ch);
                curl_close($ch);
                if ($output) {
                    return $output;
                } else {
                    return false;
                }
            }
        }
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
     */
    public function rmdir(string $dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== "." && $object !== "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object)) {
                        $this->rmdir($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
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
     * Get an array that represents directory tree.
     *
     * @param string $dir  Directory path.
     * @param string $bool Include sub directories. Default: dirs. Option: files.
     * @return string
     */
    public function directoryListing(string $dir, string $include = 'dirs')
    {
        $truedir = $dir;
        $dir = scandir($dir);
        if ($include === 'files') { // dynamic function based on second param
            $direct = 'is_dir';
        } elseif ($include === 'dirs') {
            $direct = 'is_file';
        }
        foreach ($dir as $k => $v) {
            if (($direct($truedir . $dir[$k])) || $dir[$k] === '.' || $dir[$k] === '..') {
                unset($dir[$k]);
            }
        }
        $dir = array_values($dir);
        return $dir;
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
     */
    public function prepend(string $path, string $data): bool
    {
        if (! $this->exists($path, false)) {
            return false;
        }

        $dataFile = file_put_contents($path, $data . $this->getContents($path), LOCK_EX);

        return $dataFile !== false;
    }

    /**
     * Appends data to a file.
     */
    public function append(string $path, string $data): bool
    {
        if (! $this->exists($path, false)) {
            return false;
        }

        $dataFile = file_put_contents($path, $data, FILE_APPEND | LOCK_EX);

        return $dataFile !== false;
    }

    /**
     * Updates a file.
     */
    public function update(string $path, string $data): bool
    {
        if (! $this->exists($path, false)) {
            return false;
        }

        $dataFile = file_put_contents($path, $data, LOCK_EX);

        return $dataFile !== false;
    }
}
