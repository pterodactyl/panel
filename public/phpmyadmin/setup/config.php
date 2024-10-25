<?php
/**
 * Front controller for config view / download and clear
 */

declare(strict_types=1);

use PhpMyAdmin\Config\Forms\Setup\ConfigForm;
use PhpMyAdmin\Core;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\ConfigGenerator;
use PhpMyAdmin\Url;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

// phpcs:disable PSR1.Files.SideEffects
define('PHPMYADMIN', true);
// phpcs:enable

require ROOT_PATH . 'setup/lib/common.inc.php';

$form_display = new ConfigForm($GLOBALS['ConfigFile']);
$form_display->save('Config');

$response = ResponseRenderer::getInstance();
$response->disable();

if (isset($_POST['eol'])) {
    $_SESSION['eol'] = $_POST['eol'] === 'unix' ? 'unix' : 'win';
}

if (isset($_POST['submit_clear']) && is_scalar($_POST['submit_clear']) ? $_POST['submit_clear'] : '') {
    // Clear current config and return to main page
    $GLOBALS['ConfigFile']->resetConfigData();
    // drop post data
    $response->generateHeader303('index.php' . Url::getCommonRaw());
    exit;
}

if (isset($_POST['submit_download']) && is_scalar($_POST['submit_download']) ? $_POST['submit_download'] : '') {
    // Output generated config file
    Core::downloadHeader('config.inc.php', 'text/plain');
    $response->disable();
    echo ConfigGenerator::getConfigFile($GLOBALS['ConfigFile']);
    exit;
}

// Show generated config file in a <textarea>
$response->generateHeader303('index.php' . Url::getCommonRaw(['page' => 'config']));
exit;
