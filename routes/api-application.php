<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Application;

Route::get('/version', [Application\VersionController::class]);

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
    Route::get('/{databaseHost}', [Application\Databases\DatabaseController::class, 'view']);

    Route::post('/', [Application\Databases\DatabaseController::class, 'store']);

    Route::patch('/{databaseHost}', [Application\Databases\DatabaseController::class, 'update']);

    Route::delete('/{databaseHost}', [Application\Databases\DatabaseController::class, 'delete']);
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
    Route::get('/{egg}', [Application\Eggs\EggController::class, 'view']);
    Route::get('/{egg}/export', [Application\Eggs\EggController::class, 'export']);

    Route::post('/', [Application\Eggs\EggController::class, 'store']);
    Route::post('/{egg}/variables', [Application\Eggs\EggVariableController::class, 'store']);

    Route::patch('/{egg}', [Application\Eggs\EggController::class, 'update']);
    Route::patch('/{egg}/variables', [Application\Eggs\EggVariableController::class, 'update']);

    Route::delete('/{egg}', [Application\Eggs\EggController::class, 'delete']);
    Route::delete('/{egg}/variables/{eggVariable}', [Application\Eggs\EggVariableController::class, 'delete']);
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
    Route::get('/{location}', [Application\Locations\LocationController::class, 'view']);

    Route::post('/', [Application\Locations\LocationController::class, 'store']);

    Route::patch('/{location}', [Application\Locations\LocationController::class, 'update']);

    Route::delete('/{location}', [Application\Locations\LocationController::class, 'delete']);
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
    Route::get('/{mount}', [Application\Mounts\MountController::class, 'view']);

    Route::post('/', [Application\Mounts\MountController::class, 'store']);

    Route::put('/{mount}/eggs', [Application\Mounts\MountController::class, 'addEggs']);
    Route::put('/{mount}/nodes', [Application\Mounts\MountController::class, 'addNodes']);

    Route::patch('/{mount}', [Application\Mounts\MountController::class, 'update']);

    Route::delete('/{mount}', [Application\Mounts\MountController::class, 'delete']);
    Route::delete('/{mount}/eggs', [Application\Mounts\MountController::class, 'deleteEggs']);
    Route::delete('/{mount}/nodes', [Application\Mounts\MountController::class, 'deleteNodes']);
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
    Route::get('/{nest}', [Application\Nests\NestController::class, 'view']);
    Route::get('/{nest}/eggs', [Application\Eggs\EggController::class, 'index']);

    Route::post('/', [Application\Nests\NestController::class, 'store']);
    Route::post('/{nest}/import', [Application\Nests\NestController::class, 'import']);

    Route::patch('/{nest}', [Application\Nests\NestController::class, 'update']);

    Route::delete('/{nest}', [Application\Nests\NestController::class, 'delete']);
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
    Route::get('/{node}', [Application\Nodes\NodeController::class, 'view']);
    Route::get('/{node}/configuration', [Application\Nodes\NodeConfigurationController::class, '__invoke']);
    Route::get('/{node}/information', [Application\Nodes\NodeInformationController::class, '__invoke']);

    Route::post('/', [Application\Nodes\NodeController::class, 'store']);

    Route::patch('/{node}', [Application\Nodes\NodeController::class, 'update']);

    Route::delete('/{node}', [Application\Nodes\NodeController::class, 'delete']);

    Route::group(['prefix' => '/{node}/allocations'], function () {
        Route::get('/', [Application\Nodes\AllocationController::class, 'index']);
        Route::post('/', [Application\Nodes\AllocationController::class, 'store']);
        Route::delete('/{allocation}', [Application\Nodes\AllocationController::class, 'delete']);
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
    Route::get('/{role}', [Application\Roles\RoleController::class, 'view']);

    Route::post('/', [Application\Roles\RoleController::class, 'store']);

    Route::patch('/{role}', [Application\Roles\RoleController::class, 'update']);

    Route::delete('/{role}', [Application\Roles\RoleController::class, 'delete']);
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
    Route::get('/{server}', [Application\Servers\ServerController::class, 'view']);
    Route::get('/external/{external_id}', [Application\Servers\ExternalServerController::class, 'index']);

    Route::patch('/{server}', [Application\Servers\ServerController::class, 'update']);
    Route::patch('/{server}/startup', [Application\Servers\StartupController::class, 'index']);

    Route::post('/', [Application\Servers\ServerController::class, 'store']);
    Route::post('/{server}/suspend', [Application\Servers\ServerManagementController::class, 'suspend']);
    Route::post('/{server}/unsuspend', [Application\Servers\ServerManagementController::class, 'unsuspend']);
    Route::post('/{server}/reinstall', [Application\Servers\ServerManagementController::class, 'reinstall']);

    Route::delete('/{server}', [Application\Servers\ServerController::class, 'delete']);
    Route::delete('/{server}/{force?}', [Application\Servers\ServerController::class, 'delete']);

    // Database Management Endpoint
    Route::group(['prefix' => '/{server}/databases'], function () {
        Route::get('/', [Application\Servers\DatabaseController::class, 'index']);
        Route::get('/{database}', [Application\Servers\DatabaseController::class, 'view']);

        Route::post('/', [Application\Servers\DatabaseController::class, 'store']);
        Route::post('/{database}/reset-password', [Application\Servers\DatabaseController::class, 'resetPassword']);

        Route::delete('/{database}', [Application\Servers\DatabaseController::class, 'delete']);
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
    Route::get('/{user}', [Application\Users\UserController::class, 'view']);
    Route::get('/external/{external_id}', [Application\Users\ExternalUserController::class, 'index']);

    Route::post('/', [Application\Users\UserController::class, 'store']);

    Route::patch('/{user}', [Application\Users\UserController::class, 'update']);

    Route::delete('/{user}', [Application\Users\UserController::class, 'delete']);
});
