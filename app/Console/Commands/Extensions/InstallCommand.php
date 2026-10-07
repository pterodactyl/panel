<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Pterodactyl\Contracts\Extensions\InstallsExtensions;
use Pterodactyl\Exceptions\Extensions\ExtensionAlreadyInstalledException;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionUrlDownloader;

#[Description('Install an extension package into the extensions directory.')]
#[Signature('p:extension:install
                            {path : Path to a .pteroext/.zip archive or an unpacked extension directory, or a signed install URL.}
                            {--enable : Enable the extension (and run its migrations) after installing.}
                            {--replace : Replace an installed extension with the same id without asking.}')]
class InstallCommand extends Command
{
    public function handle(InstallsExtensions $installer, ExtensionUrlDownloader $urls): int
    {
        $download = null;

        try {
            $path = $this->argument('path');
            // A URL is only ever a signed install URL: its signature is verified before
            // anything is downloaded, so the panel never installs from an arbitrary address.
            if ($urls->isUrl($path)) {
                $this->components->info('Verifying the install URL and downloading the extension.');
                $path = $download = $urls->download($path);
            }

            $manifest = $this->install($installer, $path);
        } catch (InvalidExtensionException $invalidExtensionException) {
            $this->components->error($invalidExtensionException->getMessage());

            return self::FAILURE;
        } finally {
            if ($download !== null) {
                rescue(fn () => File::delete($download));
            }
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
    private function install(InstallsExtensions $installer, string $path): ExtensionManifest
    {
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
