<?php
/**
 * Format converter
 */

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

use PhpMyAdmin\Util;

use function bin2hex;
use function hex2bin;
use function inet_ntop;
use function inet_pton;
use function ip2long;
use function long2ip;
use function strpos;
use function substr;

/**
 * Format converter
 */
class FormatConverter
{
    /**
     * Transforms a binary to an IP
     *
     * @param mixed $buffer Data to transform
     *
     * @return false|string
     */
    public static function binaryToIp($buffer, bool $isBinary)
    {
        if (strpos($buffer, '0x') !== 0) {
            return $isBinary ? bin2hex($buffer) : $buffer;
        }

        $ipHex = substr($buffer, 2);
        $ipBin = hex2bin($ipHex);

        if ($ipBin === false) {
            return $buffer;
        }

        return @inet_ntop($ipBin);
    }

    /**
     * Transforms an IP to a binary
     *
     * @param mixed $buffer Data to transform
     *
     * @return string
     */
    public static function ipToBinary($buffer)
    {
        $val = @inet_pton($buffer);
        if ($val !== false) {
            return '0x' . bin2hex($val);
        }

        return $buffer;
    }

    /**
     * Transforms an IP to a long
     *
     * @param string $buffer Data to transform
     *
     * @return int|string
     */
    public static function ipToLong(string $buffer)
    {
        $ipLong = ip2long($buffer);
        if ($ipLong === false) {
            return $buffer;
        }

        return $ipLong;
    }

    /**
     * Transforms a long to an IP
     *
     * @param mixed $buffer Data to transform
     */
    public static function longToIp($buffer): string
    {
        if (! Util::isInteger($buffer) || $buffer < 0 || $buffer > 4294967295) {
            return $buffer;
        }

        return (string) long2ip((int) $buffer);
    }
}
