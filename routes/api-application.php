<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Application;

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/users
|
*/

Route::group(['prefix' => '/users'], function () {
    Route::get('/', [Application\Users\UserController::class, 'index'])->name('api.application.users');
    Route::get('/{user}', [Application\Users\UserController::class, 'view'])->name('api.application.users.view');
    Route::get('/external/{external_id}', [Application\Users\ExternalUserController::class, 'index'])->name('api.application.users.external');

    Route::post('/', [Application\Users\UserController::class, 'store']);
    Route::patch('/{user}', [Application\Users\UserController::class, 'update']);

    Route::delete('/{user}', [Application\Users\UserController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/nodes
|
*/
Route::group(['prefix' => '/nodes'], function () {
    Route::get('/', [Application\Nodes\NodeController::class, 'index'])->name('api.application.nodes');
    Route::get('/deployable', Application\Nodes\NodeDeploymentController::class);
    Route::get('/{node}', [Application\Nodes\NodeController::class, 'view'])->name('api.application.nodes.view');
    Route::get('/{node}/configuration', Application\Nodes\NodeConfigurationController::class);

    Route::post('/', [Application\Nodes\NodeController::class, 'store']);
    Route::patch('/{node}', [Application\Nodes\NodeController::class, 'update']);

    Route::delete('/{node}', [Application\Nodes\NodeController::class, 'delete']);

    Route::group(['prefix' => '/{node}/allocations'], function () {
        Route::get('/', [Application\Nodes\AllocationController::class, 'index'])->name('api.application.allocations');
        Route::post('/', [Application\Nodes\AllocationController::class, 'store']);
        Route::delete('/{allocation}', [Application\Nodes\AllocationController::class, 'delete'])->name('api.application.allocations.view');
    });
});

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/locations
|
*/
Route::group(['prefix' => '/locations'], function () {
    Route::get('/', [Application\Locations\LocationController::class, 'index'])->name('api.applications.locations');
    Route::get('/{location}', [Application\Locations\LocationController::class, 'view'])->name('api.application.locations.view');

    Route::post('/', [Application\Locations\LocationController::class, 'store']);
    Route::patch('/{location}', [Application\Locations\LocationController::class, 'update']);

    Route::delete('/{location}', [Application\Locations\LocationController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/servers
|
*/
Route::group(['prefix' => '/servers'], function () {
    Route::get('/', [Application\Servers\ServerController::class, 'index'])->name('api.application.servers');
    Route::get('/{server}', [Application\Servers\ServerController::class, 'view'])->name('api.application.servers.view');
    Route::get('/external/{external_id}', [Application\Servers\ExternalServerController::class, 'index'])->name('api.application.servers.external');

    Route::patch('/{server}/details', [Application\Servers\ServerDetailsController::class, 'details'])->name('api.application.servers.details');
    Route::patch('/{server}/build', [Application\Servers\ServerDetailsController::class, 'build'])->name('api.application.servers.build');
    Route::patch('/{server}/startup', [Application\Servers\StartupController::class, 'index'])->name('api.application.servers.startup');

    Route::post('/', [Application\Servers\ServerController::class, 'store']);
    Route::post('/{server}/suspend', [Application\Servers\ServerManagementController::class, 'suspend'])->name('api.application.servers.suspend');
    Route::post('/{server}/unsuspend', [Application\Servers\ServerManagementController::class, 'unsuspend'])->name('api.application.servers.unsuspend');
    Route::post('/{server}/reinstall', [Application\Servers\ServerManagementController::class, 'reinstall'])->name('api.application.servers.reinstall');

    Route::delete('/{server}', [Application\Servers\ServerController::class, 'delete']);
    Route::delete('/{server}/{force?}', [Application\Servers\ServerController::class, 'delete']);

    // Database Management Endpoint
    Route::group(['prefix' => '/{server}/databases'], function () {
        Route::get('/', [Application\Servers\DatabaseController::class, 'index'])->name('api.application.servers.databases');
        Route::get('/{database}', [Application\Servers\DatabaseController::class, 'view'])->name('api.application.servers.databases.view');

        Route::post('/', [Application\Servers\DatabaseController::class, 'store']);
        Route::post('/{database}/reset-password', [Application\Servers\DatabaseController::class, 'resetPassword']);

        Route::delete('/{database}', [Application\Servers\DatabaseController::class, 'delete']);
    });
});

/*
|--------------------------------------------------------------------------
| Nest Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/nests
|
*/
Route::group(['prefix' => '/nests'], function () {
    Route::get('/', [Application\Nests\NestController::class, 'index'])->name('api.application.nests');
    Route::get('/{nest}', [Application\Nests\NestController::class, 'view'])->name('api.application.nests.view');

    // Egg Management Endpoint
    Route::group(['prefix' => '/{nest}/eggs'], function () {
        Route::get('/', [Application\Nests\EggController::class, 'index'])->name('api.application.nests.eggs');
        Route::get('/{egg}', [Application\Nests\EggController::class, 'view'])->name('api.application.nests.eggs.view');
    });
});
