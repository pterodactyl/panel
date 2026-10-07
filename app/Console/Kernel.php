<?php

declare(strict_types=1);

namespace Pterodactyl\Console;

use Illuminate\Console\Application;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Pterodactyl\Services\Extensions\ExtensionConsoleRegistry;

/**
 * Laravel's console kernel, except that extension commands reach the console application
 * last. Framework and panel commands are registered while the application is constructed,
 * many of them lazily, so only afterwards can a name that is already taken be refused
 * instead of silently replaced.
 */
class Kernel extends ConsoleKernel
{
    protected function getArtisan(): Application
    {
        if ($this->artisan instanceof Application) {
            return $this->artisan;
        }

        $artisan = parent::getArtisan();
        if ($this->app->bound(ExtensionConsoleRegistry::class)) {
            $this->app->make(ExtensionConsoleRegistry::class)->resolveCommands($artisan);
        }

        return $artisan;
    }

    /** Laravel only discovers the configured command paths for its own kernel class. */
    protected function shouldDiscoverCommands(): bool
    {
        return true;
    }
}
