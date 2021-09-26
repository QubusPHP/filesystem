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

use Aws\S3\S3ClientInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter as LeagueAwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\Visibility;
use Qubus\Config\ConfigContainer;

final class AwsS3FlysystemAdapter extends LeagueAwsS3V3Adapter implements FlysystemAdapter
{
    private ConfigContainer $config;

    public function __construct(S3ClientInterface $client, ConfigContainer $config)
    {
        $this->config = $config;

        parent::__construct(
            $client,
            $this->config->getConfigKey('filesystem.awsS3.bucket'),
            $this->config->getConfigKey('filesystem.awsS3.prefix'),
            new PortableVisibilityConverter(
                $this->config->getConfigKey('filesystem.awsS3.visibility', Visibility::PUBLIC)
            )
        );
    }
}
