<?php
/**
 * Front controller for setup script
 */

declare(strict_types=1);

use PhpMyAdmin\Controllers\Setup\ConfigController;
use PhpMyAdmin\Controllers\Setup\FormController;
use PhpMyAdmin\Controllers\Setup\HomeController;
use PhpMyAdmin\Controllers\Setup\ServersController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Header;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

/** @psalm-suppress InvalidGlobal */
global $cfg;

// phpcs:disable PSR1.Files.SideEffects
define('PHPMYADMIN', true);
// phpcs:enable

require ROOT_PATH . 'setup/lib/common.inc.php';

if (@file_exists(CONFIG_FILE) && ! $cfg['DBG']['demo']) {
    Core::fatalError(__('Configuration already exists, setup is disabled!'));
}

$page = 'index';
if (isset($_GET['page']) && in_array($_GET['page'], ['form', 'config', 'servers'], true)) {
    $page = $_GET['page'];
}

Core::noCacheHeader();

// Sent security-related headers
(new Header())->sendHttpHeaders();

if ($page === 'form') {
    echo (new FormController($GLOBALS['ConfigFile'], new Template()))([
        'formset' => $_GET['formset'] ?? null,
    ]);

    return;
}

if ($page === 'config') {
    echo (new ConfigController($GLOBALS['ConfigFile'], new Template()))([
        'formset' => $_GET['formset'] ?? null,
        'eol' => $_GET['eol'] ?? null,
    ]);

    return;
}

if ($page === 'servers') {
    $controller = new ServersController($GLOBALS['ConfigFile'], new Template());
    if (isset($_GET['mode']) && $_GET['mode'] === 'remove' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $controller->destroy([
            'id' => $_GET['id'] ?? null,
        ]);
        header('Location: index.php' . Url::getCommonRaw());

        return;
    }

    echo $controller->index([
        'formset' => $_GET['formset'] ?? null,
        'mode' => $_GET['mode'] ?? null,
        'id' => $_GET['id'] ?? null,
    ]);

    return;
}

echo (new HomeController($GLOBALS['ConfigFile'], new Template()))([
    'formset' => $_GET['formset'] ?? null,
    'version_check' => $_GET['version_check'] ?? null,
]);
