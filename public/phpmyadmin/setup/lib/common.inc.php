<?php

declare(strict_types=1);

use PhpMyAdmin\Common;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\DatabaseInterface;

if (PHP_VERSION_ID < 70205) {
    die('<p>PHP 7.2.5+ is required.</p><p>Currently installed version is: ' . PHP_VERSION . '</p>');
}

if (! defined('PHPMYADMIN')) {
    exit;
}

require_once ROOT_PATH . 'libraries/constants.php';

/**
 * Activate autoloader
 */
if (! @is_readable(AUTOLOAD_FILE)) {
    die(
        '<p>File <samp>' . AUTOLOAD_FILE . '</samp> missing or not readable.</p>'
        . '<p>Most likely you did not run Composer to '
        . '<a href="https://docs.phpmyadmin.net/en/latest/setup.html#installing-from-git">'
        . 'install library files</a>.</p>'
    );
}

require AUTOLOAD_FILE;

chdir('..');

$isMinimumCommon = true;

Common::run();

// use default error handler
restore_error_handler();

// Save current language in a cookie, required since we set $isMinimumCommon
$GLOBALS['config']->setCookie('pma_lang', (string) $GLOBALS['lang']);
$GLOBALS['config']->set('is_setup', true);

$GLOBALS['ConfigFile'] = new ConfigFile();
$GLOBALS['ConfigFile']->setPersistKeys(
    [
        'DefaultLang',
        'ServerDefault',
        'UploadDir',
        'SaveDir',
        'Servers/1/verbose',
        'Servers/1/host',
        'Servers/1/port',
        'Servers/1/socket',
        'Servers/1/auth_type',
        'Servers/1/user',
        'Servers/1/password',
    ]
);

$GLOBALS['dbi'] = DatabaseInterface::load();

// allows for redirection even after sending some data
ob_start();
