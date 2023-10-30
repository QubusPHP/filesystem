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
use League\Flysystem\InMemory\InMemoryFilesystemAdapter as LeagueInMemoryFilesystemAdapter;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Exception;

final class InMemoryFlysystemAdapter extends LeagueInMemoryFilesystemAdapter implements FilesystemAdapter
{
    /**
     * @throws Exception
     */
    public function __construct(ConfigContainer $config)
    {
        parent::__construct($config->getConfigKey('filesystem.disks.inmemory.visibility', 'public'));
    }
}
