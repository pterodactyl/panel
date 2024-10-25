<?php

namespace Psalm;

use Composer\Autoload\ClassLoader;
use Psalm\Internal\CliUtils;

// phpcs:disable PSR1.Files.SideEffects
require_once __DIR__ . '/Psalm/Internal/CliUtils.php';

/** @deprecated going to be removed in Psalm 5 */
function requireAutoloaders(string $current_dir, bool $has_explicit_root, string $vendor_dir): ?ClassLoader
{
    return CliUtils::requireAutoloaders($current_dir, $has_explicit_root, $vendor_dir);
}

/** @deprecated going to be removed in Psalm 5 */
function getVendorDir(string $current_dir): string
{
    return CliUtils::getVendorDir($current_dir);
}

/**
 * @return list<string>
 * @deprecated going to be removed in Psalm 5
 */
function getArguments(): array
{
    return CliUtils::getArguments();
}

/**
 * @param  string|array|null|false $f_paths
 *
 * @return list<string>|null
 * @deprecated going to be removed in Psalm 5
 */
function getPathsToCheck($f_paths): ?array
{
    return CliUtils::getPathsToCheck($f_paths);
}

/**
 * @psalm-pure
 * @deprecated going to be removed in Psalm 5
 */
function getPsalmHelpText(): string
{
    return CliUtils::getPsalmHelpText();
}

/** @deprecated going to be removed in Psalm 5 */
function initialiseConfig(
    ?string $path_to_config,
    string $current_dir,
    string $output_format,
    ?ClassLoader $first_autoloader,
    bool $create_if_non_existent = false
): Config {
    return CliUtils::initializeConfig(
        $path_to_config,
        $current_dir,
        $output_format,
        $first_autoloader,
        $create_if_non_existent
    );
}

/** @deprecated going to be removed in Psalm 5 */
function update_config_file(Config $config, string $config_file_path, string $baseline_path): void
{
    CliUtils::updateConfigFile($config, $config_file_path, $baseline_path);
}

/** @deprecated going to be removed in Psalm 5 */
function get_path_to_config(array $options): ?string
{
    return CliUtils::getPathToConfig($options);
}

/**
 * @psalm-pure
 * @deprecated going to be removed in Psalm 5
 */
function getMemoryLimitInBytes(): int
{
    return CliUtils::getMemoryLimitInBytes();
}
