<?php

declare(strict_types=1);

namespace Pterodactyl\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionActionDecorators;
use Pterodactyl\Services\Extensions\ExtensionAssetPublisher;
use Pterodactyl\Services\Extensions\ExtensionConsoleRegistry;
use Pterodactyl\Services\Extensions\ExtensionFailureAttributor;
use Pterodactyl\Services\Extensions\ExtensionFieldRegistry;
use Pterodactyl\Services\Extensions\ExtensionHeadTags;
use Pterodactyl\Services\Extensions\ExtensionLock;
use Pterodactyl\Services\Extensions\ExtensionManifestValidator;
use Pterodactyl\Services\Extensions\ExtensionPermissionRegistry;
use Pterodactyl\Services\Extensions\ExtensionProviderLoader;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Services\Extensions\ExtensionSettingsRegistry;
use Pterodactyl\Support\JsonValueGuard;
use Throwable;

class ExtensionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExtensionManifestValidator::class);
        $this->app->singleton(ExtensionAssetPublisher::class);
        $this->app->singleton(ExtensionSettingsRegistry::class);
        $this->app->singleton(ExtensionPermissionRegistry::class);
        $this->app->singleton(ExtensionFieldRegistry::class);
        $this->app->singleton(ExtensionRepository::class);
        $this->app->singleton(ExtensionLock::class);
        $this->app->singleton(ExtensionProviderLoader::class);
        $this->app->singleton(ExtensionActionDecorators::class);
        $this->app->singleton(ExtensionConsoleRegistry::class);
        $this->app->singleton(ExtensionHeadTags::class);
        $this->app->singleton(ExtensionFailureAttributor::class);

        if (! config('extensions.enabled')) {
            return;
        }

        // Registered during the provider registration phase, then executed after
        // the core app has booted. Application::register() immediately runs
        // Laravel's normal provider boot path inside the loader's failure boundary.
        $this->app->booted(fn () => $this->app->make(ExtensionProviderLoader::class)->registerProviders($this->app->make(ExtensionRepository::class)->enabled()));
    }

    public function boot(): void
    {
        // Root path routes of extensions are public, so they are throttled per client.
        RateLimiter::for('extensions.root', function (Request $request): Limit {
            $user = $request->user();

            return Limit::perMinute(JsonValueGuard::integer(config('extensions.root_routes_per_minute')))
                ->by($user instanceof User ? $user->uuid : $request->ip());
        });

        AboutCommand::add('Extensions', fn (): array => $this->about());

        if (! config('extensions.enabled')) {
            return;
        }

        // The console application starts after every extension provider has been
        // committed, so the registry only ever hands over what booted successfully. Its
        // commands are added by Pterodactyl\Console\Kernel once the panel's own exist; the
        // scheduler is wired the way core's own schedule is (bootstrap/app.php).
        Artisan::starting(static function (Artisan $artisan): void {
            $app = $artisan->getLaravel();
            $registry = $app->make(ExtensionConsoleRegistry::class);

            $app->afterResolving(Schedule::class, static fn (Schedule $schedule) => $registry->schedule($schedule));
            if ($app->resolved(Schedule::class)) {
                $registry->schedule($app->make(Schedule::class));
            }
        });

        Exceptions::reportable(function (Throwable $throwable): void {
            $this->app->make(ExtensionFailureAttributor::class)->attribute($throwable);
        });
    }

    /**
     * The `php artisan about` rows for extensions: each installed one with its version and
     * state, the way Laravel packages report themselves there.
     *
     * @return array<string, string>
     */
    private function about(): array
    {
        if (! config('extensions.enabled')) {
            return ['Status' => 'OFF'];
        }

        $extensions = $this->app->make(ExtensionRepository::class);
        $records = $extensions->records();
        $rows = [];
        foreach ($extensions->discovered() as $manifest) {
            $record = $records->get($manifest->id);
            $state = match (true) {
                $record === null => 'not registered',
                $record->error !== null => $record->enabled ? 'enabled, failing' : 'disabled, failing',
                $record->enabled => 'enabled',
                default => 'disabled',
            };
            $rows[$manifest->id] = sprintf('%s (%s)', $manifest->version, $state);
        }

        foreach (array_keys($extensions->discoveryErrors()) as $directory) {
            $rows[$directory] = 'invalid manifest';
        }

        return $rows === [] ? ['Installed' => 'none'] : $rows;
    }
}
