<?php

declare(strict_types=1);

use Qubus\Config\Collection;
use Qubus\Exception\Data\TypeException;
use Qubus\FileSystem\Adapter\FtpFlysystemAdapter;
use Qubus\FileSystem\Adapter\InMemoryFlysystemAdapter;
use Qubus\FileSystem\Adapter\LocalFlysystemAdapter;
use Qubus\FileSystem\Adapter\SftpFlysystemAdapter;

it('constructs local, memory, FTP, and SFTP adapters with backwards-compatible defaults', function (): void {
    $config = new Collection([]);

    expect(new LocalFlysystemAdapter($config, sys_get_temp_dir()))
        ->toBeInstanceOf(LocalFlysystemAdapter::class)
        ->and(new InMemoryFlysystemAdapter($config))->toBeInstanceOf(InMemoryFlysystemAdapter::class)
        ->and(new FtpFlysystemAdapter($config))->toBeInstanceOf(FtpFlysystemAdapter::class)
        ->and(new SftpFlysystemAdapter($config))->toBeInstanceOf(SftpFlysystemAdapter::class);
});

it('rejects invalid adapter configuration before an adapter is used', function (string $adapter, array $disks): void {
    $config = new Collection([]);
    $config->setConfigKey('filesystem', ['disks' => $disks]);

    expect(fn () => new $adapter($config))->toThrow(TypeException::class);
})->with([
    'FTP port'             => [FtpFlysystemAdapter::class, ['ftp' => ['port' => '21']]],
    'memory visibility'    => [InMemoryFlysystemAdapter::class, ['inmemory' => ['visibility' => 123]]],
    'local permissions'    => [
        LocalFlysystemAdapter::class,
        ['local' => ['permission' => ['file' => ['public' => '0644']]]],
    ],
    'SFTP fingerprint list' => [SftpFlysystemAdapter::class, ['sftp' => ['fingerprint' => [123]]]],
]);
