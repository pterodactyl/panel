<?php

declare(strict_types=1);

namespace Pterodactyl\Providers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Support\ServiceProvider;
use Pterodactyl\Http\ViewComposers\AssetComposer;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function boot(): void
    {
        $this->app->make(Factory::class)->composer('*', AssetComposer::class);
    }
}
