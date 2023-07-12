<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Application;

Route::get('/version', [Application\VersionController::class, '__invoke']);

/*
|--------------------------------------------------------------------------
| Database Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/databases
|
*/
Route::group(['prefix' => '/databases'], function () {
    Route::get('/', [Application\Databases\DatabaseController::class, 'index']);
    Route::get('/{databaseHost:id}', [Application\Databases\DatabaseController::class, 'view']);

    Route::post('/', [Application\Databases\DatabaseController::class, 'store']);

    Route::patch('/{databaseHost:id}', [Application\Databases\DatabaseController::class, 'update']);

    Route::delete('/{databaseHost:id}', [Application\Databases\DatabaseController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Egg Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/eggs
|
*/
Route::group(['prefix' => '/eggs'], function () {
    Route::get('/{egg:id}', [Application\Eggs\EggController::class, 'view']);
    Route::get('/{egg:id}/export', [Application\Eggs\EggController::class, 'export']);

    Route::post('/', [Application\Eggs\EggController::class, 'store']);
    Route::post('/{egg:id}/variables', [Application\Eggs\EggVariableController::class, 'store']);

    Route::patch('/{egg:id}', [Application\Eggs\EggController::class, 'update']);
    Route::patch('/{egg:id}/variables', [Application\Eggs\EggVariableController::class, 'update']);

    Route::delete('/{egg:id}', [Application\Eggs\EggController::class, 'delete']);
    Route::delete('/{egg:id}/variables/{eggVariable:id}', [Application\Eggs\EggVariableController::class, 'delete']);
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
    Route::get('/', [Application\Locations\LocationController::class, 'index']);
    Route::get('/{location:id}', [Application\Locations\LocationController::class, 'view']);

    Route::post('/', [Application\Locations\LocationController::class, 'store']);

    Route::patch('/{location:id}', [Application\Locations\LocationController::class, 'update']);

    Route::delete('/{location:id}', [Application\Locations\LocationController::class, 'delete']);
});

/*
|--------------------------------------------------------------------------
| Mount Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/mounts
|
*/
Route::group(['prefix' => '/mounts'], function () {
    Route::get('/', [Application\Mounts\MountController::class, 'index']);
    Route::get('/{mount:id}', [Application\Mounts\MountController::class, 'view']);

    Route::post('/', [Application\Mounts\MountController::class, 'store']);

    Route::put('/{mount:id}/eggs', [Application\Mounts\MountController::class, 'addEggs']);
    Route::put('/{mount:id}/nodes', [Application\Mounts\MountController::class, 'addNodes']);

    Route::patch('/{mount:id}', [Application\Mounts\MountController::class, 'update']);

    Route::delete('/{mount:id}', [Application\Mounts\MountController::class, 'delete']);
    Route::delete('/{mount:id}/eggs', [Application\Mounts\MountController::class, 'deleteEggs']);
    Route::delete('/{mount:id}/nodes', [Application\Mounts\MountController::class, 'deleteNodes']);
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
    Route::get('/', [Application\Nests\NestController::class, 'index']);
    Route::get('/{nest:id}', [Application\Nests\NestController::class, 'view']);
    Route::get('/{nest:id}/eggs', [Application\Eggs\EggController::class, 'index']);

    Route::post('/', [Application\Nests\NestController::class, 'store']);
    Route::post('/{nest:id}/import', [Application\Nests\NestController::class, 'import']);

    Route::patch('/{nest:id}', [Application\Nests\NestController::class, 'update']);

    Route::delete('/{nest:id}', [Application\Nests\NestController::class, 'delete']);
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
    Route::get('/', [Application\Nodes\NodeController::class, 'index']);
    Route::get('/deployable', [Application\Nodes\NodeDeploymentController::class, '__invoke']);
    Route::get('/{node:id}', [Application\Nodes\NodeController::class, 'view']);
    Route::get('/{node:id}/configuration', [Application\Nodes\NodeConfigurationController::class, '__invoke']);
    Route::get('/{node:id}/information', [Application\Nodes\NodeInformationController::class, '__invoke']);

    Route::post('/', [Application\Nodes\NodeController::class, 'store']);

    Route::patch('/{node:id}', [Application\Nodes\NodeController::class, 'update']);

    Route::delete('/{node:id}', [Application\Nodes\NodeController::class, 'delete']);

    Route::group(['prefix' => '/{node:id}/allocations'], function () {
        Route::get('/', [Application\Nodes\AllocationController::class, 'index']);
        Route::post('/', [Application\Nodes\AllocationController::class, 'store']);
        Route::delete('/{allocation:id}', [Application\Nodes\AllocationController::class, 'delete']);
    });
});

/*
|--------------------------------------------------------------------------
| Role Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/roles
|
*/
Route::group(['prefix' => '/roles'], function () {
    Route::get('/', [Application\Roles\RoleController::class, 'index']);
    Route::get('/{role:id}', [Application\Roles\RoleController::class, 'view']);

    Route::post('/', [Application\Roles\RoleController::class, 'store']);

    Route::patch('/{role:id}', [Application\Roles\RoleController::class, 'update']);

    Route::delete('/{role:id}', [Application\Roles\RoleController::class, 'delete']);
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
    Route::get('/', [Application\Servers\ServerController::class, 'index']);
    Route::get('/{server:id}', [Application\Servers\ServerController::class, 'view']);
    Route::get('/external/{external_id}', [Application\Servers\ExternalServerController::class, 'index']);

    Route::patch('/{server:id}', [Application\Servers\ServerController::class, 'update']);
    Route::patch('/{server:id}/startup', [Application\Servers\StartupController::class, 'index']);

    Route::post('/', [Application\Servers\ServerController::class, 'store']);
    Route::post('/{server:id}/suspend', [Application\Servers\ServerManagementController::class, 'suspend']);
    Route::post('/{server:id}/unsuspend', [Application\Servers\ServerManagementController::class, 'unsuspend']);
    Route::post('/{server:id}/reinstall', [Application\Servers\ServerManagementController::class, 'reinstall']);

    Route::delete('/{server}', [Application\Servers\ServerController::class, 'delete']);
    Route::delete('/{server:id}/{force?}', [Application\Servers\ServerController::class, 'delete']);

    // Database Management Endpoint
    Route::group(['prefix' => '/{server:id}/databases'], function () {
        Route::get('/', [Application\Servers\DatabaseController::class, 'index']);
        Route::get('/{database:id}', [Application\Servers\DatabaseController::class, 'view']);

        Route::post('/', [Application\Servers\DatabaseController::class, 'store']);
        Route::post('/{database:id}/reset-password', [Application\Servers\DatabaseController::class, 'resetPassword']);

        Route::delete('/{database:id}', [Application\Servers\DatabaseController::class, 'delete']);
    });
});

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/users
|
*/
Route::group(['prefix' => '/users'], function () {
    Route::get('/', [Application\Users\UserController::class, 'index']);
    Route::get('/{user:id}', [Application\Users\UserController::class, 'view']);
    Route::get('/external/{external_id}', [Application\Users\ExternalUserController::class, 'index']);

    Route::post('/', [Application\Users\UserController::class, 'store']);

    Route::patch('/{user:id}', [Application\Users\UserController::class, 'update']);

    Route::delete('/{user:id}', [Application\Users\UserController::class, 'delete']);
});
