<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Application\Eggs\EggController;
use Pterodactyl\Http\Controllers\Api\Application\Locations\LocationController;
use Pterodactyl\Http\Controllers\Api\Application\Nodes\AllocationController;
use Pterodactyl\Http\Controllers\Api\Application\Nodes\NodeConfigurationController;
use Pterodactyl\Http\Controllers\Api\Application\Nodes\NodeController;
use Pterodactyl\Http\Controllers\Api\Application\Nodes\NodeDeploymentController;
use Pterodactyl\Http\Controllers\Api\Application\Servers\DatabaseController;
use Pterodactyl\Http\Controllers\Api\Application\Servers\ExternalServerController;
use Pterodactyl\Http\Controllers\Api\Application\Servers\ServerController;
use Pterodactyl\Http\Controllers\Api\Application\Servers\ServerDetailsController;
use Pterodactyl\Http\Controllers\Api\Application\Servers\ServerManagementController;
use Pterodactyl\Http\Controllers\Api\Application\Servers\StartupController;
use Pterodactyl\Http\Controllers\Api\Application\Tags\TagController;
use Pterodactyl\Http\Controllers\Api\Application\Users\ExternalUserController;
use Pterodactyl\Http\Controllers\Api\Application\Users\UserController;

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/users
|
*/

Route::group(['prefix' => '/users'], function (): void {
    Route::get('/', [UserController::class, 'index'])->name('api.application.users');
    Route::get('/{user:id}', [UserController::class, 'view'])->name('api.application.users.view');
    Route::get('/external/{external_id}', [ExternalUserController::class, 'index'])->name('api.application.users.external');

    Route::post('/', [UserController::class, 'store']);
    Route::patch('/{user:id}', [UserController::class, 'update']);

    Route::delete('/{user:id}', [UserController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/nodes
|
*/
Route::group(['prefix' => '/nodes'], function (): void {
    Route::get('/', [NodeController::class, 'index'])->name('api.application.nodes');
    Route::get('/deployable', NodeDeploymentController::class);
    Route::get('/{node:id}', [NodeController::class, 'view'])->name('api.application.nodes.view');
    Route::get('/{node:id}/configuration', NodeConfigurationController::class);

    Route::post('/', [NodeController::class, 'store']);
    Route::patch('/{node:id}', [NodeController::class, 'update'])->name('api.application.nodes.update');

    Route::delete('/{node:id}', [NodeController::class, 'delete']);

    Route::group(['prefix' => '/{node:id}/allocations'], function (): void {
        Route::get('/', [AllocationController::class, 'index'])->name('api.application.allocations');
        Route::post('/', [AllocationController::class, 'store']);
        Route::delete('/{allocation:id}', [AllocationController::class, 'delete'])->name('api.application.allocations.view');
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
Route::group(['prefix' => '/locations'], function (): void {
    Route::get('/', [LocationController::class, 'index'])->name('api.applications.locations');
    Route::get('/{location:id}', [LocationController::class, 'view'])->name('api.application.locations.view');

    Route::post('/', [LocationController::class, 'store']);
    Route::patch('/{location:id}', [LocationController::class, 'update']);

    Route::delete('/{location:id}', [LocationController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Egg Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/eggs
|
*/
Route::group(['prefix' => '/eggs'], function (): void {
    Route::get('/', [EggController::class, 'index'])->name('api.application.eggs');
    Route::get('/{egg:id}', [EggController::class, 'view'])->name('api.application.eggs.view');
});

/*
|--------------------------------------------------------------------------
| Tag Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/tags
|
*/
Route::group(['prefix' => '/tags'], function (): void {
    Route::get('/', [TagController::class, 'index'])->name('api.application.tags');
    Route::get('/{tag:id}', [TagController::class, 'view'])->name('api.application.tags.view');
});

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/servers
|
*/
Route::group(['prefix' => '/servers'], function (): void {
    Route::get('/', [ServerController::class, 'index'])->name('api.application.servers');
    Route::get('/{server:id}', [ServerController::class, 'view'])->name('api.application.servers.view');
    Route::get('/external/{external_id}', [ExternalServerController::class, 'index'])->name('api.application.servers.external');

    Route::patch('/{server:id}/details', [ServerDetailsController::class, 'details'])->name('api.application.servers.details');
    Route::patch('/{server:id}/build', [ServerDetailsController::class, 'build'])->name('api.application.servers.build');
    Route::patch('/{server:id}/startup', [StartupController::class, 'index'])->name('api.application.servers.startup');

    Route::post('/', [ServerController::class, 'store']);
    Route::post('/{server:id}/suspend', [ServerManagementController::class, 'suspend'])->name('api.application.servers.suspend');
    Route::post('/{server:id}/unsuspend', [ServerManagementController::class, 'unsuspend'])->name('api.application.servers.unsuspend');
    Route::post('/{server:id}/reinstall', [ServerManagementController::class, 'reinstall'])->name('api.application.servers.reinstall');

    Route::delete('/{server:id}', [ServerController::class, 'delete']);
    Route::delete('/{server:id}/{force?}', [ServerController::class, 'delete']);

    // Database Management Endpoint
    Route::group(['prefix' => '/{server:id}/databases'], function (): void {
        Route::get('/', [DatabaseController::class, 'index'])->name('api.application.servers.databases');
        Route::get('/{database:id}', [DatabaseController::class, 'view'])->name('api.application.servers.databases.view');

        Route::post('/', [DatabaseController::class, 'store']);
        Route::post('/{database:id}/reset-password', [DatabaseController::class, 'resetPassword']);

        Route::delete('/{database:id}', [DatabaseController::class, 'delete']);
    });
});
