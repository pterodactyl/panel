<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Extensions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Contracts\Extensions\InstallsExtensions;
use Pterodactyl\Contracts\Extensions\SetsExtensionEnabled;
use Pterodactyl\Events\Extensions\ExtensionInstalled;
use Pterodactyl\Events\Extensions\ExtensionInstalling;
use Pterodactyl\Exceptions\Extensions\ExtensionAlreadyInstalledException;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Models\Extension;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionCompatibility;
use Pterodactyl\Services\Extensions\ExtensionLock;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionPackageFiles;
use Pterodactyl\Services\Extensions\ExtensionPackageLocator;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionRuntimeRefresher;

final readonly class InstallExtension implements InstallsExtensions
{
    public function __construct(
        private ExtensionPackageLocator $packages,
        private ExtensionPackageFiles $files,
        private ExtensionManifestValidator $validator,
        private ExtensionRepository $extensions,
        private ExtensionAssetPublisher $assets,
        private SetsExtensionEnabled $state,
        private ExtensionCompatibility $compatibility,
        private ExtensionLock $lock,
        private ExtensionRuntimeRefresher $runtime,
    ) {}

    public function install(string $source, bool $enable = false, bool $replace = false): ExtensionManifest
    {
        return $this->packages->using($source, fn (string $directory): ExtensionManifest => $this->installFromDirectory($directory, $source, $enable, $replace));
    }

    private function installFromDirectory(string $directory, string $source, bool $enable, bool $replace): ExtensionManifest
    {
        $manifest = $this->validator->fromDirectory($directory);

        return $this->lock->run($manifest->id, fn (): ExtensionManifest => $this->installPackage($manifest, $source, $enable, $replace));
    }

    private function installPackage(ExtensionManifest $manifest, string $source, bool $enable, bool $replace): ExtensionManifest
    {
        $target = $this->extensions->directory().DIRECTORY_SEPARATOR.$manifest->id;
        $previous = Extension::query()->where('identifier', $manifest->id)->first();
        $this->checkReplacement($manifest, $target, $previous, $replace);
        $activate = $enable || ($previous->enabled ?? false);
        $this->checkCompatibility($manifest, $activate);
        // Assets are only published once the extension is enabled; this just checks the build.
        throw_if($reason = $this->assets->unusableBuildReason($manifest), InvalidExtensionException::class, $reason);
        Event::dispatch(new ExtensionInstalling($manifest, $source));

        try {
            $installed = $this->files->replace(
                $manifest,
                $target,
                fn (string $directory): ExtensionManifest => $this->registerPackage($directory, $activate),
            );
        } finally {
            $this->extensions->flushDiscovery();
        }

        DB::afterCommit(function () use ($installed, $activate): void {
            if (! $activate) {
                rescue(fn () => $this->runtime->refresh());
            }

            rescue(fn () => Event::dispatch(new ExtensionInstalled($installed)));
        });

        return $installed;
    }

    /**
     * A package with an installed id replaces that extension's files and keeps an enabled one
     * enabled, which runs its migrations and provider, so it needs the caller's confirmation.
     * Reinstalling the installed copy in place replaces nothing.
     */
    private function checkReplacement(ExtensionManifest $manifest, string $target, ?Extension $previous, bool $replace): void
    {
        if ($replace || $this->files->inPlace($manifest, $target) || (! $previous instanceof Extension && ! file_exists($target))) {
            return;
        }

        $installed = $previous->version ?? rescue(fn (): string => $this->validator->fromDirectory($target)->version, report: false);

        throw new ExtensionAlreadyInstalledException($manifest->id, $installed, $manifest->version, (bool) ($previous->enabled ?? false));
    }

    private function checkCompatibility(ExtensionManifest $manifest, bool $activate): void
    {
        if ($activate) {
            $this->compatibility->assertCompatible($manifest, $this->extensions->configuredEnabled());

            return;
        }

        $this->compatibility->assertRuntimeCompatible($manifest);
    }

    private function registerPackage(string $directory, bool $activate): ExtensionManifest
    {
        $manifest = $this->validator->fromDirectory($directory);

        if ($activate) {
            $this->state->setEnabled($manifest->id, true, $manifest);

            return $manifest;
        }

        Extension::query()->updateOrCreate(
            ['identifier' => $manifest->id],
            ['version' => $manifest->version, 'error' => null],
        );

        return $manifest;
    }
}
