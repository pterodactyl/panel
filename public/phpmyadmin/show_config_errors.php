<?php
/**
 * Simple wrapper just to enable error reporting and include config
 */

declare(strict_types=1);

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

// rfc2616 - Section 14.21
header('Expires: ' . gmdate(DATE_RFC1123));
// HTTP/1.1
header('Cache-Control: no-store, no-cache, must-revalidate,  pre-check=0, post-check=0, max-age=0');

header('Pragma: no-cache'); // HTTP/1.0
// test case: exporting a database into a .gz file with Safari
// would produce files not having the current time
// (added this header for Safari but should not harm other browsers)
header('Last-Modified: ' . gmdate(DATE_RFC1123));

header('Content-Type: text/html; charset=utf-8');

// phpcs:disable PSR1.Files.SideEffects
define('PHPMYADMIN', true);
// phpcs:enable

require ROOT_PATH . 'libraries/constants.php';

// issue #16256 - This only works with php 8.0+
if (function_exists('error_reporting')) {
    error_reporting(E_ALL);
}

/**
 * Read config file.
 */
if (is_readable(CONFIG_FILE)) {
    /** @psalm-suppress MissingFile */
    include CONFIG_FILE;
}
