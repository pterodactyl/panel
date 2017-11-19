<?php

namespace Pterodactyl\Providers;

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
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map()
    {
//        Route::middleware(['api'])->prefix('/api/user')
//              ->namespace($this->namespace . '\API\User')
//              ->group(base_path('routes/api.php'));

        Route::middleware(['web', 'auth', 'csrf'])
             ->namespace($this->namespace . '\Base')
             ->group(base_path('routes/base.php'));

        Route::middleware(['web', 'auth', 'admin', 'csrf'])->prefix('/admin')
             ->namespace($this->namespace . '\Admin')
             ->group(base_path('routes/admin.php'));

        Route::middleware(['web', 'csrf'])->prefix('/auth')
             ->namespace($this->namespace . '\Auth')
             ->group(base_path('routes/auth.php'));

        Route::middleware(['web', 'csrf', 'auth', 'server', 'subuser.auth'])->prefix('/server/{server}')
             ->namespace($this->namespace . '\Server')
             ->group(base_path('routes/server.php'));

        Route::middleware(['api', 'api..user_level:admin'])->prefix('/api/admin')
            ->namespace($this->namespace . '\API\Admin')
            ->group(base_path('routes/api-admin.php'));

        Route::middleware(['daemon'])->prefix('/api/remote')
            ->namespace($this->namespace . '\API\Remote')
            ->group(base_path('routes/api-remote.php'));

        Route::middleware(['web', 'daemon-old'])->prefix('/daemon')
             ->namespace($this->namespace . '\Daemon')
             ->group(base_path('routes/daemon.php'));
    }
}
