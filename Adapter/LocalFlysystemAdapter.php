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

namespace Qubus\FileSystem\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter as LeagueLocalFileSystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\MimeTypeDetection\MimeTypeDetector;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\FileSystem\ConfigValue;

use const LOCK_EX;

final class LocalFlysystemAdapter extends LeagueLocalFileSystemAdapter implements FilesystemAdapter
{
    /**
     * @throws Exception
     */
    public function __construct(
        public readonly ConfigContainer $config,
        ?string $location = null,
        int $writeFlags = LOCK_EX,
        int $linkHandling = self::DISALLOW_LINKS,
        ?MimeTypeDetector $mimeTypeDetector = null
    ) {
        parent::__construct(
            $location ?? ConfigValue::string($this->config, 'filesystem.disks.local.root', '/var/www'),
            PortableVisibilityConverter::fromArray($this->setVisibilityConverter()),
            $writeFlags,
            $linkHandling,
            $mimeTypeDetector
        );
    }

    /**
     * The directory and file visibility options.
     *
     * @return array{
     *     file: array{public: int, private: int},
     *     dir: array{public: int, private: int}
     * }
     * @throws Exception
     */
    private function setVisibilityConverter(): array
    {
        return [
            'file' => [
                'public'  => ConfigValue::integer(
                    $this->config,
                    'filesystem.disks.local.permission.file.public',
                    0644
                ),
                'private' => ConfigValue::integer(
                    $this->config,
                    'filesystem.disks.local.permission.file.private',
                    0600
                ),
            ],
            'dir'  => [
                'public'  => ConfigValue::integer(
                    $this->config,
                    'filesystem.disks.local.permission.dir.public',
                    0755
                ),
                'private' => ConfigValue::integer(
                    $this->config,
                    'filesystem.disks.local.permission.dir.private',
                    0700
                ),
            ],
        ];
    }
}
