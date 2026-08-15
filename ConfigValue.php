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

namespace Qubus\FileSystem;

use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;

use function array_values;
use function get_debug_type;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function sprintf;

final class ConfigValue
{
    /**
     * @param ConfigContainer $config
     * @param string $key
     * @param mixed|null $default
     * @return string
     * @throws TypeException
     * @throws Exception
     */
    public static function string(ConfigContainer $config, string $key, mixed $default = null): string
    {
        $value = $config->getConfigKey($key, $default);
        if (! is_string($value)) {
            throw self::invalidType($key, 'a string', $value);
        }

        return $value;
    }

    /**
     * @param ConfigContainer $config
     * @param string $key
     * @param int $default
     * @return int
     * @throws Exception
     * @throws TypeException
     */
    public static function integer(ConfigContainer $config, string $key, int $default): int
    {
        $value = $config->getConfigKey($key, $default);
        if (! is_int($value)) {
            throw self::invalidType($key, 'an integer', $value);
        }

        return $value;
    }

    /**
     * @param ConfigContainer $config
     * @param string $key
     * @param bool $default
     * @return bool
     * @throws Exception
     * @throws TypeException
     */
    public static function boolean(ConfigContainer $config, string $key, bool $default): bool
    {
        $value = $config->getConfigKey($key, $default);
        if (! is_bool($value)) {
            throw self::invalidType($key, 'a boolean', $value);
        }

        return $value;
    }

    /**
     * @param ConfigContainer $config
     * @param string $key
     * @param string|null $default
     * @return string|null
     * @throws Exception
     * @throws TypeException
     */
    public static function nullableString(
        ConfigContainer $config,
        string $key,
        ?string $default = null
    ): ?string {
        $value = $config->getConfigKey($key, $default);
        if ($value !== null && ! is_string($value)) {
            throw self::invalidType($key, 'a string or null', $value);
        }

        return $value;
    }

    /**
     * @param ConfigContainer $config
     * @param string $key
     * @return bool|null
     * @throws Exception
     * @throws TypeException
     */
    public static function nullableBoolean(ConfigContainer $config, string $key): ?bool
    {
        $value = $config->getConfigKey($key);
        if ($value !== null && ! is_bool($value)) {
            throw self::invalidType($key, 'a boolean or null', $value);
        }

        return $value;
    }

    /**
     * @param ConfigContainer $config
     * @param string $key
     * @return string|list<string>|null
     * @throws Exception
     * @throws TypeException
     */
    public static function nullableStringList(ConfigContainer $config, string $key): string|array|null
    {
        $value = $config->getConfigKey($key);
        if ($value === null || is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (! is_string($item)) {
                    throw self::invalidType($key, 'a string, an array of strings, or null', $value);
                }
            }

            return array_values($value);
        }

        throw self::invalidType($key, 'a string, an array of strings, or null', $value);
    }

    /**
     * @param string $key
     * @param string $expected
     * @param mixed $value
     * @return TypeException
     * @throws \Qubus\Exception\Exception
     */
    private static function invalidType(string $key, string $expected, mixed $value): TypeException
    {
        return new TypeException(
            sprintf('Configuration value "%s" must be %s, %s given.', $key, $expected, get_debug_type($value))
        );
    }
}
