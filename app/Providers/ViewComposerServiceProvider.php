<?php

namespace Pterodactyl\Providers;

use Illuminate\Support\ServiceProvider;
use Pterodactyl\Http\ViewComposers\StoreComposer;
use Pterodactyl\Http\ViewComposers\SettingComposer;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function boot()
    {
        $this->app->make('view')->composer('*', SettingComposer::class);
        $this->app->make('view')->composer('*', StoreComposer::class);
    }
}
