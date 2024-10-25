<?php

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

final class SessionCache
{
    private static function key(): string
    {
        global $cfg, $server;

        $key = 'server_' . $server;

        if (isset($cfg['Server']['user'])) {
            return $key . '_' . $cfg['Server']['user'];
        }

        return $key;
    }

    public static function has(string $name): bool
    {
        return isset($_SESSION['cache'][self::key()][$name]);
    }

    /**
     * @return mixed|null
     */
    public static function get(string $name, ?callable $defaultValueCallback = null)
    {
        if (self::has($name)) {
            return $_SESSION['cache'][self::key()][$name];
        }

        if ($defaultValueCallback !== null) {
            $value = $defaultValueCallback();
            self::set($name, $value);

            return $value;
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    public static function set(string $name, $value): void
    {
        $_SESSION['cache'][self::key()][$name] = $value;
    }

    public static function remove(string $name): void
    {
        unset($_SESSION['cache'][self::key()][$name]);
    }
}
