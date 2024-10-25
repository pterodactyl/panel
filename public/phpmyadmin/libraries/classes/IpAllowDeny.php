<?php
/**
 * This library is used with the server IP allow/deny host authentication
 * feature
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use function bin2hex;
use function dechex;
use function explode;
use function hash_equals;
use function hexdec;
use function inet_pton;
use function ip2long;
use function is_array;
use function mb_strpos;
use function mb_strtolower;
use function mb_substr;
use function min;
use function preg_match;
use function str_replace;
use function substr_replace;

/**
 * PhpMyAdmin\IpAllowDeny class
 */
class IpAllowDeny
{
    /**
     * Matches for IPv4 or IPv6 addresses
     *
     * @param string $testRange string of IP range to match
     * @param string $ipToTest  string of IP to test against range
     */
    public function ipMaskTest($testRange, $ipToTest): bool
    {
        if (mb_strpos($testRange, ':') > -1 || mb_strpos($ipToTest, ':') > -1) {
            // assume IPv6
            $result = $this->ipv6MaskTest($testRange, $ipToTest);
        } else {
            $result = $this->ipv4MaskTest($testRange, $ipToTest);
        }

        return $result;
    }

    /**
     * Based on IP Pattern Matcher
     * Originally by J.Adams <jna@retina.net>
     * Found on <https://www.php.net/manual/en/function.ip2long.php>
     * Modified for phpMyAdmin
     *
     * Matches:
     * xxx.xxx.xxx.xxx        (exact)
     * xxx.xxx.xxx.[yyy-zzz]  (range)
     * xxx.xxx.xxx.xxx/nn     (CIDR)
     *
     * Does not match:
     * xxx.xxx.xxx.xx[yyy-zzz]  (range, partial octets not supported)
     *
     * @param string $testRange string of IP range to match
     * @param string $ipToTest  string of IP to test against range
     */
    public function ipv4MaskTest($testRange, $ipToTest): bool
    {
        $result = true;
        $match = preg_match('|([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)/([0-9]+)|', $testRange, $regs);
        if ($match) {
            // performs a mask match
            $ipl = ip2long($ipToTest);
            $rangel = ip2long($regs[1] . '.' . $regs[2] . '.' . $regs[3] . '.' . $regs[4]);

            $maskl = 0;

            for ($i = 0; $i < 31; $i++) {
                if ($i >= $regs[5] - 1) {
                    continue;
                }

                $maskl += 2 ** (30 - $i);
            }

            return ($maskl & $rangel) == ($maskl & $ipl);
        }

        // range based
        $maskocts = explode('.', $testRange);
        $ipocts = explode('.', $ipToTest);

        // perform a range match
        for ($i = 0; $i < 4; $i++) {
            if (preg_match('|\[([0-9]+)\-([0-9]+)\]|', $maskocts[$i], $regs)) {
                if (($ipocts[$i] > $regs[2]) || ($ipocts[$i] < $regs[1])) {
                    $result = false;
                }
            } else {
                if ($maskocts[$i] <> $ipocts[$i]) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    /**
     * IPv6 matcher
     * CIDR section taken from https://stackoverflow.com/a/10086404
     * Modified for phpMyAdmin
     *
     * Matches:
     * xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx
     * (exact)
     * xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:[yyyy-zzzz]
     * (range, only at end of IP - no subnets)
     * xxxx:xxxx:xxxx:xxxx/nn
     * (CIDR)
     *
     * Does not match:
     * xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xx[yyy-zzz]
     * (range, partial octets not supported)
     *
     * @param string $test_range string of IP range to match
     * @param string $ip_to_test string of IP to test against range
     */
    public function ipv6MaskTest($test_range, $ip_to_test): bool
    {
        $result = true;

        // convert to lowercase for easier comparison
        $test_range = mb_strtolower($test_range);
        $ip_to_test = mb_strtolower($ip_to_test);

        $is_cidr = mb_strpos($test_range, '/') > -1;
        $is_range = mb_strpos($test_range, '[') > -1;
        $is_single = ! $is_cidr && ! $is_range;

        $ip_hex = bin2hex((string) inet_pton($ip_to_test));

        if ($is_single) {
            $range_hex = bin2hex((string) inet_pton($test_range));

            return hash_equals($ip_hex, $range_hex);
        }

        if ($is_range) {
            // what range do we operate on?
            $range_match = [];
            $match = preg_match('/\[([0-9a-f]+)\-([0-9a-f]+)\]/', $test_range, $range_match);
            if ($match) {
                $range_start = $range_match[1];
                $range_end = $range_match[2];

                // get the first and last allowed IPs
                $first_ip = str_replace($range_match[0], $range_start, $test_range);
                $first_hex = bin2hex((string) inet_pton($first_ip));
                $last_ip = str_replace($range_match[0], $range_end, $test_range);
                $last_hex = bin2hex((string) inet_pton($last_ip));

                // check if the IP to test is within the range
                $result = ($ip_hex >= $first_hex && $ip_hex <= $last_hex);
            }

            return $result;
        }

        if ($is_cidr) {
            // Split in address and prefix length
            [$first_ip, $subnet] = explode('/', $test_range);

            // Parse the address into a binary string
            $first_bin = inet_pton($first_ip);
            $first_hex = bin2hex((string) $first_bin);

            $flexbits = 128 - (int) $subnet;

            // Build the hexadecimal string of the last address
            $last_hex = $first_hex;

            $pos = 31;
            while ($flexbits > 0) {
                // Get the character at this position
                $orig = mb_substr($last_hex, $pos, 1);

                // Convert it to an integer
                $origval = hexdec($orig);

                // OR it with (2^flexbits)-1, with flexbits limited to 4 at a time
                $newval = $origval | 2 ** min(4, $flexbits) - 1;

                // Convert it back to a hexadecimal character
                $new = dechex($newval);

                // And put that character back in the string
                $last_hex = substr_replace($last_hex, $new, $pos, 1);

                // We processed one nibble, move to previous position
                $flexbits -= 4;
                --$pos;
            }

            // check if the IP to test is within the range
            $result = ($ip_hex >= $first_hex && $ip_hex <= $last_hex);
        }

        return $result;
    }

    /**
     * Runs through IP Allow rules the use of it below for more information
     *
     * @see     Core::getIp()
     */
    public function allow(): bool
    {
        return $this->allowDeny('allow');
    }

    /**
     * Runs through IP Deny rules the use of it below for more information
     *
     * @see     Core::getIp()
     */
    public function deny(): bool
    {
        return $this->allowDeny('deny');
    }

    /**
     * Runs through IP Allow/Deny rules the use of it below for more information
     *
     * @see     Core::getIp()
     *
     * @param string $type 'allow' | 'deny' type of rule to match
     */
    private function allowDeny($type): bool
    {
        global $cfg;

        // Grabs true IP of the user and returns if it can't be found
        $remote_ip = Core::getIp();
        if (empty($remote_ip)) {
            return false;
        }

        // copy username
        $username = $cfg['Server']['user'];

        // copy rule database
        if (isset($cfg['Server']['AllowDeny']['rules'])) {
            $rules = $cfg['Server']['AllowDeny']['rules'];
            if (! is_array($rules)) {
                $rules = [];
            }
        } else {
            $rules = [];
        }

        // lookup table for some name shortcuts
        $shortcuts = [
            'all' => '0.0.0.0/0',
            'localhost' => '127.0.0.1/8',
        ];

        // Provide some useful shortcuts if server gives us address:
        if (Core::getenv('SERVER_ADDR')) {
            $shortcuts['localnetA'] = Core::getenv('SERVER_ADDR') . '/8';
            $shortcuts['localnetB'] = Core::getenv('SERVER_ADDR') . '/16';
            $shortcuts['localnetC'] = Core::getenv('SERVER_ADDR') . '/24';
        }

        foreach ($rules as $rule) {
            // extract rule data
            $rule_data = explode(' ', $rule);

            // check for rule type
            if ($rule_data[0] != $type) {
                continue;
            }

            // check for username
            if (
                ($rule_data[1] !== '%') //wildcarded first
                && (! hash_equals($rule_data[1], $username))
            ) {
                continue;
            }

            // check if the config file has the full string with an extra
            // 'from' in it and if it does, just discard it
            if ($rule_data[2] === 'from') {
                $rule_data[2] = $rule_data[3];
            }

            // Handle shortcuts with above array
            if (isset($shortcuts[$rule_data[2]])) {
                $rule_data[2] = $shortcuts[$rule_data[2]];
            }

            // Add code for host lookups here
            // Excluded for the moment

            // Do the actual matching now
            if ($this->ipMaskTest($rule_data[2], $remote_ip)) {
                return true;
            }
        }

        return false;
    }
}
