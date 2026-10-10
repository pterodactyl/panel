<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Extensions;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Contracts\Extensions\SetsExtensionEnabled;
use Pterodactyl\Events\Extensions\ExtensionDisabled;
use Pterodactyl\Events\Extensions\ExtensionDisabling;
use Pterodactyl\Events\Extensions\ExtensionEnabled;
use Pterodactyl\Events\Extensions\ExtensionEnabling;
use Pterodactyl\Exceptions\Extensions\InvalidExtensionException;
use Pterodactyl\Models\Extension;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionCompatibility;
use Pterodactyl\Services\Extensions\ExtensionDirectories;
use Pterodactyl\Services\Extensions\ExtensionLock;
use Pterodactyl\Services\Extensions\ExtensionManifest;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionRuntimeRefresher;

final readonly class SetExtensionEnabled implements SetsExtensionEnabled
{
    public function __construct(
        private ExtensionRepository $extensions,
        private ExtensionAssetPublisher $assets,
        private Migrator $migrator,
        private ExtensionCompatibility $compatibility,
        private ExtensionLock $lock,
        private ExtensionRuntimeRefresher $runtime,
        private ExtensionDirectories $directories,
    ) {}

    public function setEnabled(string $identifier, bool $enabled, ?ExtensionManifest $manifest = null): void
    {
        $this->directories->assertWritable();
        $this->lock->run($identifier, fn () => $this->changeState($identifier, $enabled, $manifest));
    }

    private function changeState(string $identifier, bool $enabled, ?ExtensionManifest $manifest): void
    {
        $manifest ??= $this->extensions->discovered()->get($identifier);
        throw_if($manifest === null, InvalidExtensionException::class, "Extension \"{$identifier}\" is not installed.");
        $this->checkStateChange($manifest, $enabled);
        Event::dispatch($enabled ? new ExtensionEnabling($manifest) : new ExtensionDisabling($manifest));

        if ($enabled) {
            $this->activate($manifest);
        } else {
            $this->persist($manifest, false);
        }

        $this->extensions->flushDiscovery();
        DB::afterCommit(function () use ($manifest, $enabled): void {
            rescue(fn () => $this->runtime->refresh());
            rescue(fn () => Event::dispatch($enabled ? new ExtensionEnabled($manifest) : new ExtensionDisabled($manifest)));
        });
    }

    private function checkStateChange(ExtensionManifest $manifest, bool $enabled): void
    {
        if (! $enabled) {
            $this->compatibility->assertCanDisable($manifest->id, $this->extensions->enabled());

            return;
        }

        $this->compatibility->assertCompatible($manifest, $this->extensions->configuredEnabled());
        $this->compatibility->assertMigrationsAreUnique($manifest, $this->extensions->discovered());
        throw_if($reason = $this->assets->unusableBuildReason($manifest), InvalidExtensionException::class, $reason);
    }

    private function activate(ExtensionManifest $manifest): void
    {
        $this->runMigrations($manifest);
        $this->assets->publishWith($manifest, fn () => $this->persist($manifest, true));
    }

    private function persist(ExtensionManifest $manifest, bool $enabled): void
    {
        Extension::query()->updateOrCreate(
            ['identifier' => $manifest->id],
            ['version' => $manifest->version, 'enabled' => $enabled, 'error' => null],
        );
    }

    private function runMigrations(ExtensionManifest $manifest): void
    {
        $migrations = $manifest->path('database', 'migrations');
        if (! is_dir($migrations)) {
            return;
        }

        $this->migrator->path($migrations);
        $exitCode = Artisan::call('migrate', ['--force' => true, '--path' => $migrations, '--realpath' => true]);
        throw_if($exitCode !== 0, InvalidExtensionException::class, "Extension \"{$manifest->id}\" migrations failed. Completed database changes may require manual recovery.");
    }
}
