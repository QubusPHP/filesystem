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
use League\Flysystem\Ftp\FtpAdapter as LeagueFtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;
use Qubus\FileSystem\ConfigValue;

use const FTP_BINARY;

final class FtpFlysystemAdapter extends LeagueFtpAdapter implements FilesystemAdapter
{
    /**
     * @throws Exception
     */
    public function __construct(public readonly ConfigContainer $config)
    {
        parent::__construct(FtpConnectionOptions::fromArray($this->setFtpConnectionOptions()));
    }

    /**
     * FTP connection options.
     *
     * @return array{
     *     host: string,
     *     root: string,
     *     username: string,
     *     password: string,
     *     port: int,
     *     ssl: bool,
     *     timeout: int,
     *     utf8: bool,
     *     passive: bool,
     *     transferMode: int,
     *     systemType: ?string,
     *     ignorePassiveAddress: ?bool,
     *     timestampsOnUnixListingsEnabled: bool,
     *     recurseManually: bool
     * }
     * @throws Exception
     */
    private function setFtpConnectionOptions(): array
    {
        return [
            'host'                            => ConfigValue::string(
                $this->config,
                'filesystem.disks.ftp.host',
                'localhost'
            ),
            'root'                            => ConfigValue::string(
                $this->config,
                'filesystem.disks.ftp.root',
                '/var/www/'
            ),
            'username'                        => ConfigValue::string(
                $this->config,
                'filesystem.disks.ftp.username',
                'root'
            ),
            'password'                        => ConfigValue::string(
                $this->config,
                'filesystem.disks.ftp.password',
                'root'
            ),
            'port'                            => ConfigValue::integer($this->config, 'filesystem.disks.ftp.port', 21),
            'ssl'                             => ConfigValue::boolean($this->config, 'filesystem.disks.ftp.ssl', false),
            'timeout'                         => ConfigValue::integer(
                $this->config,
                'filesystem.disks.ftp.timeout',
                90
            ),
            'utf8'                            => ConfigValue::boolean(
                $this->config,
                'filesystem.disks.ftp.utf8',
                false
            ),
            'passive'                         => ConfigValue::boolean(
                $this->config,
                'filesystem.disks.ftp.passive',
                true
            ),
            'transferMode'                    => ConfigValue::integer(
                $this->config,
                'filesystem.disks.ftp.transferMode',
                FTP_BINARY
            ),
            'systemType'                      => ConfigValue::nullableString(
                $this->config,
                'filesystem.disks.ftp.systemType'
            ),
            'ignorePassiveAddress'            => ConfigValue::nullableBoolean(
                $this->config,
                'filesystem.disks.ftp.ignorePassiveAddress'
            ),
            'timestampsOnUnixListingsEnabled' => ConfigValue::boolean(
                $this->config,
                'filesystem.disks.ftp.enableTimestamps',
                false
            ),
            'recurseManually'                 => ConfigValue::boolean(
                $this->config,
                'filesystem.disks.ftp.recurseManually',
                true
            ),
        ];
    }
}
