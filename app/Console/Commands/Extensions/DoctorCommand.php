<?php

declare(strict_types=1);

namespace Pterodactyl\Console\Commands\Extensions;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionCompatibility;
use Pterodactyl\Services\Extensions\ExtensionComposerInspector;
use Pterodactyl\Services\Extensions\ExtensionIconCatalog;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Themes\ThemeRepository;
use Pterodactyl\Services\Themes\ViewCatalog;

#[Description('Check an extension or theme manifest, compatibility, and built frontend before installation.')]
#[Signature('p:extension:doctor {path : Path to an unpacked extension package.}')]
class DoctorCommand extends Command
{
    public function handle(ExtensionManifestValidator $validator, ExtensionCompatibility $compatibility, ExtensionRepository $extensions, ExtensionAssetPublisher $assets, ExtensionIconCatalog $icons, ExtensionComposerInspector $composer, ViewCatalog $views, ThemeRepository $themes): int
    {
        try {
            $manifest = $validator->fromDirectory($this->argument('path'));
            $compatibility->assertCompatible($manifest, $extensions->configuredEnabled());
            $compatibility->assertMigrationsAreUnique($manifest, $extensions->discovered());
            throw_if($reason = $assets->unusableBuildReason($manifest), InvalidExtensionException::class, $reason);
            foreach ($manifest->autoload as $directory) {
                throw_unless(is_dir($manifest->path($directory)), InvalidExtensionException::class, "Autoload directory \"{$directory}\" is missing.");
            }

            $unknown = $views->unknown($manifest->views);
            throw_if($unknown !== [], InvalidExtensionException::class, 'Manifest "ui.views" names views the panel does not have: '.implode(', ', $unknown).'.');
        } catch (InvalidExtensionException $invalidExtensionException) {
            $this->components->error($invalidExtensionException->getMessage());

            return self::FAILURE;
        }

        foreach ($icons->unknownIcons($manifest) as $icon) {
            $this->components->warn("Icon \"{$icon}\" is not a lucide icon this panel ships; the default icon is shown instead.");
        }

        if (($problem = $assets->iconProblem($manifest)) !== null) {
            $this->components->warn($problem.' The extension list shows its initials instead.');
        }

        if ($manifest->isTheme() && ($mismatch = $themes->coreMismatch($manifest)) !== null) {
            $this->components->warn($mismatch);
        }

        foreach ($composer->warnings($manifest) as $warning) {
            $this->components->warn($warning);
        }

        $this->components->info("{$manifest->id} v{$manifest->version}: manifest, compatibility, autoload directories, and build passed.");

        return self::SUCCESS;
    }
}
