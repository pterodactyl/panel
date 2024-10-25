<?php

declare(strict_types=1);

if (! defined('PHPMYADMIN')) {
    exit;
}

$vendorConfig = require_once ROOT_PATH . 'libraries/vendor_config.php';
if (
    ! is_array($vendorConfig) || ! isset(
        $vendorConfig['autoloadFile'],
        $vendorConfig['tempDir'],
        $vendorConfig['changeLogFile'],
        $vendorConfig['licenseFile'],
        $vendorConfig['sqlDir'],
        $vendorConfig['configFile'],
        $vendorConfig['customHeaderFile'],
        $vendorConfig['customFooterFile'],
        $vendorConfig['versionCheckDefault'],
        $vendorConfig['localePath'],
        $vendorConfig['cacheDir'],
        $vendorConfig['versionSuffix']
    )
) {
    exit;
}

// phpcs:disable PSR1.Files.SideEffects
define('AUTOLOAD_FILE', (string) $vendorConfig['autoloadFile']);
define('TEMP_DIR', (string) $vendorConfig['tempDir']);
define('CHANGELOG_FILE', (string) $vendorConfig['changeLogFile']);
define('LICENSE_FILE', (string) $vendorConfig['licenseFile']);
define('SQL_DIR', (string) $vendorConfig['sqlDir']);
define('CONFIG_FILE', (string) $vendorConfig['configFile']);
define('CUSTOM_HEADER_FILE', (string) $vendorConfig['customHeaderFile']);
define('CUSTOM_FOOTER_FILE', (string) $vendorConfig['customFooterFile']);
define('VERSION_CHECK_DEFAULT', (bool) $vendorConfig['versionCheckDefault']);
define('LOCALE_PATH', (string) $vendorConfig['localePath']);
define('CACHE_DIR', (string) $vendorConfig['cacheDir']);
define('VERSION_SUFFIX', (string) $vendorConfig['versionSuffix']);

/**
 * TCPDF workaround. Avoid referring to nonexistent files (causes warnings when open_basedir is used).
 * This is defined to avoid the TCPDF code to search for a directory outside of open_basedir.
 * This value if not used but is useful, no header logic is used for PDF exports.
 *
 * @see https://github.com/phpmyadmin/phpmyadmin/issues/16709
 */
define('K_PATH_IMAGES', ROOT_PATH);
// phpcs:enable

unset($vendorConfig);
