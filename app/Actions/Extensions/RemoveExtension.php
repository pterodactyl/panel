<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Extensions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Contracts\Extensions\RemovesExtensions;
use Pterodactyl\Events\Extensions\ExtensionRemoved;
use Pterodactyl\Events\Extensions\ExtensionRemoving;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Models\Extension;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionCompatibility;
use Pterodactyl\Services\Extensions\ExtensionLock;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionRuntimeRefresher;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;
use Pterodactyl\Services\FilesystemChanges;

final readonly class RemoveExtension implements RemovesExtensions
{
    public function __construct(
        private ExtensionRepository $extensions,
        private ExtensionAssetPublisher $assets,
        private ExtensionCompatibility $compatibility,
        private ExtensionLock $lock,
        private ExtensionRuntimeRefresher $runtime,
        private FilesystemChanges $files,
        private ExtensionSettingFiles $settingFiles,
    ) {}

    public function remove(string $identifier): void
    {
        throw_unless(preg_match(ExtensionManifest::ID_REGEX, $identifier), InvalidExtensionException::class, "Extension id \"{$identifier}\" must match ".ExtensionManifest::ID_REGEX.'.');
        $this->lock->run($identifier, fn () => $this->removePackage($identifier));
    }

    private function removePackage(string $identifier): void
    {
        $manifest = $this->extensions->discovered()->get($identifier);
        throw_if($manifest === null, InvalidExtensionException::class, "Extension \"{$identifier}\" is not installed.");
        $this->compatibility->assertCanDisable($identifier, $this->extensions->enabled());
        Event::dispatch(new ExtensionRemoving($manifest));
        $this->files->run([
            $this->extensions->directory().DIRECTORY_SEPARATOR.$identifier => null,
            $this->assets->publishedPath($identifier) => null,
        ], static function () use ($identifier): void {
            DB::transaction(static function () use ($identifier): void {
                Extension::query()->where('identifier', $identifier)->delete();
            });
        });
        $this->extensions->flushDiscovery();
        DB::afterCommit(function () use ($identifier): void {
            rescue(fn () => $this->settingFiles->purge($identifier));
            rescue(fn () => $this->runtime->refresh());
            rescue(fn () => Event::dispatch(new ExtensionRemoved($identifier)));
        });
    }
}
