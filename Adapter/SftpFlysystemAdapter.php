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
use League\Flysystem\PhpseclibV3\ConnectivityChecker;
use League\Flysystem\PhpseclibV3\SftpAdapter as LeagueSftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\FileSystem\ConfigValue;

use function get_debug_type;
use function sprintf;

final class SftpFlysystemAdapter extends LeagueSftpAdapter implements FilesystemAdapter
{
    /**
     * @throws Exception
     */
    public function __construct(public readonly ConfigContainer $config)
    {
        parent::__construct(
            $this->setSftpConnectionProvider(),
            ConfigValue::string($this->config, 'filesystem.disks.sftp.root', '/var/www'),
            PortableVisibilityConverter::fromArray($this->setVisibilityConverter())
        );
    }

    /**
     * The FTP connection provider options.
     * @throws Exception
     */
    private function setSftpConnectionProvider(): SftpConnectionProvider
    {
        $connectivityChecker = $this->config->getConfigKey('filesystem.disks.sftp.connectivity');
        if ($connectivityChecker !== null && ! $connectivityChecker instanceof ConnectivityChecker) {
            throw new TypeException(
                sprintf(
                    'SFTP connectivity checker must implement %s or be null, %s given.',
                    ConnectivityChecker::class,
                    get_debug_type($connectivityChecker)
                )
            );
        }

        return new SftpConnectionProvider(
            ConfigValue::string($this->config, 'filesystem.disks.sftp.host', 'localhost'),
            ConfigValue::string($this->config, 'filesystem.disks.sftp.username', 'root'),
            ConfigValue::nullableString($this->config, 'filesystem.disks.sftp.password', 'root'),
            ConfigValue::nullableString($this->config, 'filesystem.disks.sftp.privatekey'),
            ConfigValue::nullableString($this->config, 'filesystem.disks.sftp.passphrase'),
            ConfigValue::integer($this->config, 'filesystem.disks.sftp.port', 22),
            ConfigValue::boolean($this->config, 'filesystem.disks.sftp.useagent', false),
            ConfigValue::integer($this->config, 'filesystem.disks.sftp.timeout', 10),
            ConfigValue::integer($this->config, 'filesystem.disks.sftp.maxtries', 4),
            ConfigValue::nullableStringList($this->config, 'filesystem.disks.sftp.fingerprint'),
            $connectivityChecker
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
                    'filesystem.disks.sftp.permission.file.public',
                    0644
                ),
                'private' => ConfigValue::integer(
                    $this->config,
                    'filesystem.disks.sftp.permission.file.private',
                    0600
                ),
            ],
            'dir'  => [
                'public'  => ConfigValue::integer(
                    $this->config,
                    'filesystem.disks.sftp.permission.dir.public',
                    0755
                ),
                'private' => ConfigValue::integer(
                    $this->config,
                    'filesystem.disks.sftp.permission.dir.private',
                    0700
                ),
            ],
        ];
    }
}
