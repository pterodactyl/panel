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
     * Define the routes for the application.
     */
    public function map()
    {
        Route::middleware(['web', 'auth', 'csrf'])
            ->namespace($this->namespace . '\Base')
            ->group(base_path('routes/base.php'));

        Route::middleware(['web', 'auth', 'admin', 'csrf'])->prefix('/admin')
            ->namespace($this->namespace . '\Admin')
            ->group(base_path('routes/admin.php'));

        Route::middleware(['web', 'csrf'])->prefix('/auth')
            ->namespace($this->namespace . '\Auth')
            ->group(base_path('routes/auth.php'));

        Route::middleware(['web', 'csrf', 'auth', 'server', 'node.maintenance'])
            ->prefix('/api/server/{server}')
            ->namespace($this->namespace . '\Server')
            ->group(base_path('routes/server.php'));

        Route::middleware([
            sprintf('throttle:%s,%s', config('http.rate_limit.application'), config('http.rate_limit.application_period')),
            'api',
        ])->prefix('/api/application')
            ->namespace($this->namespace . '\Api\Application')
            ->group(base_path('routes/api-application.php'));

        Route::middleware([
            sprintf('throttle:%s,%s', config('http.rate_limit.client'), config('http.rate_limit.client_period')),
            'client-api',
        ])->prefix('/api/client')
            ->namespace($this->namespace . '\Api\Client')
            ->group(base_path('routes/api-client.php'));

        Route::middleware(['daemon'])->prefix('/api/remote')
            ->namespace($this->namespace . '\Api\Remote')
            ->group(base_path('routes/api-remote.php'));
    }
}
