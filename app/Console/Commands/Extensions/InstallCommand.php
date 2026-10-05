<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Contracts\Extensions\InstallsExtensions;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;

#[Description('Install an extension package into the extensions directory.')]
#[Signature('p:extension:install
                            {path : Path to a .pteroext/.zip archive or an unpacked extension directory.}
                            {--enable : Enable the extension (and run its migrations) after installing.}')]
class InstallCommand extends Command
{
    public function handle(InstallsExtensions $installer): int
    {
        try {
            $manifest = $installer->install($this->argument('path'), (bool) $this->option('enable'));
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
}
