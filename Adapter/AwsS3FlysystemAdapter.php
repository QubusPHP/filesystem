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

use Aws\S3\S3ClientInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as LeagueAwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Visibility;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

final class AwsS3FlysystemAdapter extends LeagueAwsS3V3Adapter implements FilesystemAdapter
{
    /**
     * @throws Exception
     */
    public function __construct(
        S3ClientInterface $client,
        public readonly ConfigContainer $config
    ) {
        parent::__construct(
            $client,
            $this->config->getConfigKey('filesystem.disks.awsS3.bucket'),
            $this->config->getConfigKey('filesystem.disks.awsS3.prefix'),
            new PortableVisibilityConverter(
                $this->config->getConfigKey('filesystem.disks.awsS3.visibility', Visibility::PUBLIC)
            )
        );
    }
}
