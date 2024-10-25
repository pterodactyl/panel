<?php

namespace Psalm\Internal\Cli;

use PackageVersions\Versions;
use Psalm\Internal\CliUtils;
use Psalm\Internal\PluginManager\Command\DisableCommand;
use Psalm\Internal\PluginManager\Command\EnableCommand;
use Psalm\Internal\PluginManager\Command\ShowCommand;
use Psalm\Internal\PluginManager\PluginListFactory;
use Symfony\Component\Console\Application;

use function dirname;
use function getcwd;

use const DIRECTORY_SEPARATOR;

// phpcs:disable PSR1.Files.SideEffects

require_once __DIR__ . '/../CliUtils.php';
require_once __DIR__ . '/../Composer.php';

final class Plugin
{
    public static function run(): void
    {
        $current_dir = (string)getcwd() . DIRECTORY_SEPARATOR;
        $vendor_dir = CliUtils::getVendorDir($current_dir);
        CliUtils::requireAutoloaders($current_dir, false, $vendor_dir);

        $app = new Application('psalm-plugin', Versions::getVersion('vimeo/psalm'));

        $psalm_root = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;

        $plugin_list_factory = new PluginListFactory($current_dir, $psalm_root);

        $app->addCommands([
            new ShowCommand($plugin_list_factory),
            new EnableCommand($plugin_list_factory),
            new DisableCommand($plugin_list_factory),
        ]);

        $app->setDefaultCommand('show');
        $app->run();
    }
}
