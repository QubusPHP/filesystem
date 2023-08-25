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
use League\Flysystem\PhpseclibV3\SftpAdapter as LeagueSftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

final class SftpFlysystemAdapter extends LeagueSftpAdapter implements FilesystemAdapter
{
    /**
     * @throws Exception
     */
    public function __construct(public readonly ConfigContainer $config)
    {
        parent::__construct(
            $this->setSftpConnectionProvider(),
            $this->config->getConfigKey('filesystem.sftp.root', '/var/www'),
            PortableVisibilityConverter::fromArray($this->setVisibilityConverter())
        );
    }

    /**
     * The FTP connection provider options.
     * @throws Exception
     */
    private function setSftpConnectionProvider(): SftpConnectionProvider
    {
        return new SftpConnectionProvider(
            $this->config->getConfigKey('filesystem.sftp.host', 'localhost'),
            $this->config->getConfigKey('filesystem.sftp.username', 'root'),
            $this->config->getConfigKey('filesystem.sftp.password', 'root'),
            $this->config->getConfigKey('filesystem.sftp.privatekey', null),
            $this->config->getConfigKey('filesystem.sftp.passphrase', null),
            $this->config->getConfigKey('filesystem.sftp.port', 22),
            $this->config->getConfigKey('filesystem.sftp.useagent', false),
            $this->config->getConfigKey('filesystem.sftp.timeout', 10),
            $this->config->getConfigKey('filesystem.sftp.maxtries', 4),
            $this->config->getConfigKey('filesystem.sftp.fingerprint', null),
            $this->config->getConfigKey('filesystem.sftp.connectivity', null)
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
                'public'  => $this->config->getConfigKey('filesystem.sftp.visibility.file.public', 0644),
                'private' => $this->config->getConfigKey('filesystem.sftp.visibility.file.private', 0600),
            ],
            'dir'  => [
                'public'  => $this->config->getConfigKey('filesystem.sftp.visibility.dir.public', 0755),
                'private' => $this->config->getConfigKey('filesystem.sftp.visibility.dir.private', 0700),
            ],
        ];
    }
}
