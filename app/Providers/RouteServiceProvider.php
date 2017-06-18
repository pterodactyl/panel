<?php

namespace Pterodactyl\Providers;

use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to the controller routes in your routes file.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'Pterodactyl\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        Route::middleware(['api'])->prefix('/api/user')
              ->namespace($this->namespace . '\API\User')
              ->group(base_path('routes/api.php'));

        Route::middleware(['api'])->prefix('/api/admin')
              ->namespace($this->namespace . '\API\Admin')
              ->group(base_path('routes/api-admin.php'));

        Route::middleware(['web', 'auth', 'csrf'])
             ->namespace($this->namespace . '\Base')
             ->group(base_path('routes/base.php'));

        Route::middleware(['web', 'auth', 'admin', 'csrf'])->prefix('/admin')
             ->namespace($this->namespace . '\Admin')
             ->group(base_path('routes/admin.php'));

        Route::middleware(['web', 'csrf'])->prefix('/auth')
             ->namespace($this->namespace . '\Auth')
             ->group(base_path('routes/auth.php'));

        Route::middleware(['web', 'auth', 'server', 'csrf'])->prefix('/server/{server}')
             ->namespace($this->namespace . '\Server')
             ->group(base_path('routes/server.php'));

        Route::middleware(['web', 'daemon'])->prefix('/daemon')
             ->namespace($this->namespace . '\Daemon')
             ->group(base_path('routes/daemon.php'));
    }
}
