<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Entrypoint\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class EntrypointServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * This is a custom namespace that you can change yourself to fit your needs.
     *
     * @var string
     */
    protected $namespace = 'Entrypoint\Http\Controllers';

    /**
     * Define the routes for the application.
     */
    public function map()
    {
        $this->mapEntrypointRoutes();
    }

    /**
     * This is the main entry-point to introduce new route files into pterodactyl. This is the cleanest way to add without introducing vendor packages.
     * @return void
     */
    protected function mapEntrypointRoutes()
    {

        /*
         * Example of an extension of the base routes.
         * Route::middleware(['web', 'auth', 'csrf'])
            ->namespace($this->namespace . '\Base')
            ->group(base_path('routes/entrypoint/base.php'));
         */
    }

}