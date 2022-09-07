<?php

/**
 * Qubus\FileSystem
 *
 * @link       https://github.com/QubusPHP/filesystem
 * @copyright  2021 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.1.0
 */

declare(strict_types=1);

namespace Qubus\FileSystem\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter as LeagueLocalFileSystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\MimeTypeDetection\MimeTypeDetector;
use Qubus\Config\ConfigContainer;

use Qubus\Exception\Exception;

use const LOCK_EX;

final class LocalFlysystemAdapter extends LeagueLocalFileSystemAdapter implements FilesystemAdapter
{
    /**
     * @throws Exception
     */
    public function __construct(
        public readonly ConfigContainer $config,
        int $writeFlags = LOCK_EX,
        int $linkHandling = self::DISALLOW_LINKS,
        ?MimeTypeDetector $mimeTypeDetector = null
    ) {
        parent::__construct(
            $this->config->getConfigKey('filesystem.local.root', '/var/www'),
            PortableVisibilityConverter::fromArray($this->setVisibilityConverter()),
            $writeFlags,
            $linkHandling,
            $mimeTypeDetector
        );
    }

    /**
     * The directory and file visibility options.
     *
     * @return array
     * @throws Exception
     */
    private function setVisibilityConverter(): array
    {
        return [
            'file' => [
                'public'  => $this->config->getConfigKey('filesystem.local.visibility.file.public', 0644),
                'private' => $this->config->getConfigKey('filesystem.local.visibility.file.private', 0600),
            ],
            'dir'  => [
                'public'  => $this->config->getConfigKey('filesystem.local.visibility.dir.public', 0755),
                'private' => $this->config->getConfigKey('filesystem.local.visibility.dir.private', 0700),
            ],
        ];
    }
}
