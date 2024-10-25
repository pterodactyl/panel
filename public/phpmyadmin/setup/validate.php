<?php
/**
 * Validation callback.
 */

declare(strict_types=1);

use PhpMyAdmin\Config\Validator;
use PhpMyAdmin\Core;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

// phpcs:disable PSR1.Files.SideEffects
define('PHPMYADMIN', true);
// phpcs:enable

require ROOT_PATH . 'setup/lib/common.inc.php';


Core::headerJSON();

$ids = isset($_POST['id']) && is_scalar($_POST['id']) ? (string) $_POST['id'] : '';
$vids = explode(',', $ids);
$vals = isset($_POST['values']) && is_scalar($_POST['values']) ? (string) $_POST['values'] : '';
$values = json_decode($vals);
if (! ($values instanceof stdClass)) {
    Core::fatalError(__('Wrong data'));
}

$values = (array) $values;
$result = Validator::validate($GLOBALS['ConfigFile'], $vids, $values, true);
if ($result === false) {
    $result = sprintf(
        __('Wrong data or no validation for %s'),
        implode(',', $vids)
    );
}

echo $result !== true ? json_encode($result) : '';
