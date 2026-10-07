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
use Pterodactyl\Models\ExtensionSetting;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionCompatibility;
use Pterodactyl\Services\Extensions\ExtensionLock;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionPermissionRegistry;
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
        ], function () use ($identifier): void {
            DB::transaction(function () use ($identifier): void {
                Extension::query()->where('identifier', $identifier)->delete();
                $this->purgeState($identifier);
            });
        });
        $this->extensions->flushDiscovery();
        DB::afterCommit(function () use ($identifier): void {
            rescue(fn () => $this->settingFiles->purge($identifier));
            rescue(fn () => $this->runtime->refresh());
            rescue(fn () => Event::dispatch(new ExtensionRemoved($identifier)));
        });
    }

    /**
     * Forget what the extension stored outside its own tables: settings (secrets included) in
     * every scope, and the `ext.<id>.*` permissions subusers were granted. Otherwise the next
     * package installed with this id would inherit them.
     */
    private function purgeState(string $identifier): void
    {
        ExtensionSetting::query()->where('extension', $identifier)->delete();

        $prefix = ExtensionPermissionRegistry::group($identifier).'.';
        Subuser::query()->where('permissions', 'like', '%"'.$prefix.'%')->lazyById()->each(function (Subuser $subuser) use ($prefix): void {
            $permissions = array_values(array_filter($subuser->permissions, fn (string $permission): bool => ! str_starts_with($permission, $prefix)));
            if ($permissions !== $subuser->permissions) {
                $subuser->update(['permissions' => $permissions]);
            }
        });
    }
}
