<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Contracts\Extensions\InstallsExtensions;
use Pterodactyl\Exceptions\Extensions\ExtensionAlreadyInstalledException;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionManifest;

#[Description('Install an extension package into the extensions directory.')]
#[Signature('p:extension:install
                            {path : Path to a .pteroext/.zip archive or an unpacked extension directory.}
                            {--enable : Enable the extension (and run its migrations) after installing.}
                            {--replace : Replace an installed extension with the same id without asking.}')]
class InstallCommand extends Command
{
    public function handle(InstallsExtensions $installer): int
    {
        try {
            $manifest = $this->install($installer);
        } catch (InvalidExtensionException $invalidExtensionException) {
            $this->components->error($invalidExtensionException->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Installed %s (%s) v%s%s.',
            $manifest->name,
            $manifest->id,
            $manifest->version,
            $this->option('enable') ? ' - enabled' : ' - enable it with p:extension:enable '.$manifest->id,
        ));

        return self::SUCCESS;
    }

    /** Replacing an installed extension needs --replace, or a yes when run interactively. */
    private function install(InstallsExtensions $installer): ExtensionManifest
    {
        $path = $this->argument('path');
        $enable = (bool) $this->option('enable');

        try {
            return $installer->install($path, $enable, (bool) $this->option('replace'));
        } catch (ExtensionAlreadyInstalledException $extensionAlreadyInstalledException) {
            $installed = $extensionAlreadyInstalledException->installedVersion === null ? 'the installed copy' : 'v'.$extensionAlreadyInstalledException->installedVersion;
            $kept = $extensionAlreadyInstalledException->enabled ? ' It stays enabled and its migrations run.' : '';
            throw_unless(
                $this->input->isInteractive() && $this->components->confirm("Extension \"{$extensionAlreadyInstalledException->identifier}\" is already installed. Replace {$installed} with v{$extensionAlreadyInstalledException->version}?{$kept}"),
                InvalidExtensionException::class,
                $extensionAlreadyInstalledException->getMessage().' Pass --replace to replace it.',
            );

            return $installer->install($path, $enable, replace: true);
        }
    }
}
