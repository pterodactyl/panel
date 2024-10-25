<?php

declare(strict_types=1);

namespace PhpMyAdmin\Server\SysInfo;

use function in_array;
use function ucfirst;

use const PHP_OS;

/**
 * Library for extracting information about system memory and cpu.
 * Currently supports all Windows and Linux platforms
 *
 * This code is based on the OS Classes from the phpsysinfo project
 * (https://phpsysinfo.github.io/phpsysinfo/)
 */
class SysInfo
{
    public const MEMORY_REGEXP = '/^(MemTotal|MemFree|Cached|Buffers|SwapCached|SwapTotal|SwapFree):\s+(.*)\s*kB/im';

    /**
     * Returns OS type used for sysinfo class
     *
     * @param string $php_os PHP_OS constant
     *
     * @return string
     */
    public static function getOs($php_os = PHP_OS)
    {
        // look for common UNIX-like systems
        $unix_like = [
            'FreeBSD',
            'DragonFly',
        ];
        if (in_array($php_os, $unix_like)) {
            $php_os = 'Linux';
        }

        return ucfirst($php_os);
    }

    /**
     * Gets SysInfo class matching current OS
     *
     * @return Base sysinfo class
     */
    public static function get()
    {
        $php_os = self::getOs();

        switch ($php_os) {
            case 'Linux':
                $sysInfo = new Linux();
                if ($sysInfo->supported()) {
                    return $sysInfo;
                }

                break;
            case 'WINNT':
                $sysInfo = new WindowsNt();
                if ($sysInfo->supported()) {
                    return $sysInfo;
                }

                break;
            case 'SunOS':
                $sysInfo = new SunOs();
                if ($sysInfo->supported()) {
                    return $sysInfo;
                }

                break;
        }

        return new Base();
    }
}
