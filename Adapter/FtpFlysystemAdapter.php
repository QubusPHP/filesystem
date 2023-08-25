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
     * @return array
     * @throws Exception
     */
    private function setFtpConnectionOptions(): array
    {
        return [
            'host'                            => $this->config->getConfigKey('filesystem.ftp.host', 'localhost'),
            'root'                            => $this->config->getConfigKey('filesystem.ftp.root', '/var/www/'),
            'username'                        => $this->config->getConfigKey('filesystem.ftp.username', 'root'),
            'password'                        => $this->config->getConfigKey('filesystem.ftp.password', 'root'),
            'port'                            => $this->config->getConfigKey('filesystem.ftp.port', 21),
            'ssl'                             => $this->config->getConfigKey('filesystem.ftp.ssl', false),
            'timeout'                         => $this->config->getConfigKey('filesystem.ftp.timeout', 90),
            'utf8'                            => $this->config->getConfigKey('filesystem.ftp.utf8', false),
            'passive'                         => $this->config->getConfigKey('filesystem.ftp.passive', true),
            'transferMode'                    => $this->config->getConfigKey('filesystem.ftp.transferMode', FTP_BINARY),
            'systemType'                      => $this->config->getConfigKey('filesystem.ftp.systemType', null),
            'ignorePassiveAddress'            => $this->config->getConfigKey(
                'filesystem.ftp.ignorePassiveAddress',
                null
            ),
            'timestampsOnUnixListingsEnabled' => $this->config->getConfigKey('filesystem.ftp.enableTimestamps', false),
            'recurseManually'                 => $this->config->getConfigKey('filesystem.ftp.recurseManually', true),
        ];
    }
}
