<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
if (! function_exists('human_readable')) {
    /**
     * Generate a human-readable filesize for a given file path.
     *
     * @param string $path
     * @param int    $precision
     * @return string
     */
    function human_readable($path, $precision = 2)
    {
        if (is_numeric($path)) {
            $i = 0;
            while (($path / 1024) > 0.9) {
                $path = $path / 1024;
                ++$i;
            }

            return round($path, $precision) . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
        }

        return app('file')->humanReadableSize($path, $precision);
    }
}

if (! function_exists('is_digit')) {
    /**
     * Deal with normal (and irritating) PHP behavior to determine if
     * a value is a non-float positive integer.
     *
     * @param mixed $value
     * @return bool
     */
    function is_digit($value)
    {
        return is_bool($value) ? false : ctype_digit(strval($value));
    }
}
