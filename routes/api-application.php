<?php

use Illuminate\Support\Facades\Route;

Route::get('/version', 'VersionController');

/*
|--------------------------------------------------------------------------
| Database Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/databases
|
*/
Route::group(['prefix' => '/databases'], function () {
    Route::get('/', 'Databases\DatabaseController@index');
    Route::get('/{databaseHost}', 'Databases\DatabaseController@view');

    Route::post('/', 'Databases\DatabaseController@store');

    Route::patch('/{databaseHost}', 'Databases\DatabaseController@update');

    Route::delete('/{databaseHost}', 'Databases\DatabaseController@delete');
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
    Route::get('/{egg}', 'Eggs\EggController@view');

    Route::post('/', 'Eggs\EggController@store');

    Route::patch('/{egg}', 'Eggs\EggController@update');

    Route::delete('/{egg}', 'Eggs\EggController@delete');
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
    Route::get('/', 'Locations\LocationController@index')->name('api.applications.locations');
    Route::get('/{location}', 'Locations\LocationController@view')->name('api.application.locations.view');

    Route::post('/', 'Locations\LocationController@store');

    Route::patch('/{location}', 'Locations\LocationController@update');

    Route::delete('/{location}', 'Locations\LocationController@delete');
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
    Route::get('/', 'Mounts\MountController@index');
    Route::get('/{mount}', 'Mounts\MountController@view');

    Route::post('/', 'Mounts\MountController@store');

    Route::put('/{mount}/eggs', 'Mounts\MountController@addEggs');
    Route::put('/{mount}/nodes', 'Mounts\MountController@addNodes');

    Route::patch('/{mount}', 'Mounts\MountController@update');

    Route::delete('/{mount}', 'Mounts\MountController@delete');
    Route::delete('/{mount}/eggs', 'Mounts\MountController@deleteEggs');
    Route::delete('/{mount}/nodes', 'Mounts\MountController@deleteNodes');
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
    Route::get('/', 'Nests\NestController@index')->name('api.application.nests');
    Route::get('/{nest}', 'Nests\NestController@view')->name('api.application.nests.view');
    Route::get('/{nest}/eggs', 'Eggs\EggController@index');

    Route::post('/', 'Nests\NestController@store');

    Route::patch('/{nest}', 'Nests\NestController@update');

    Route::delete('/{nest}', 'Nests\NestController@delete');
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
    Route::get('/', 'Nodes\NodeController@index')->name('api.application.nodes');
    Route::get('/deployable', 'Nodes\NodeDeploymentController');
    Route::get('/{node}', 'Nodes\NodeController@view')->name('api.application.nodes.view');
    Route::get('/{node}/configuration', 'Nodes\NodeConfigurationController');
    Route::get('/{node}/information', 'Nodes\NodeInformationController');

    Route::post('/', 'Nodes\NodeController@store');

    Route::patch('/{node}', 'Nodes\NodeController@update');

    Route::delete('/{node}', 'Nodes\NodeController@delete');

    Route::group(['prefix' => '/{node}/allocations'], function () {
        Route::get('/', 'Nodes\AllocationController@index')->name('api.application.allocations');
        Route::post('/', 'Nodes\AllocationController@store');
        Route::delete('/{allocation}', 'Nodes\AllocationController@delete')->name('api.application.allocations.view');
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
    Route::get('/', 'Roles\RoleController@index');
    Route::get('/{role}', 'Roles\RoleController@view');

    Route::post('/', 'Roles\RoleController@store');

    Route::patch('/{role}', 'Roles\RoleController@update');

    Route::delete('/{role}', 'Roles\RoleController@delete');
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
    Route::get('/', 'Servers\ServerController@index')->name('api.application.servers');
    Route::get('/{server}', 'Servers\ServerController@view')->name('api.application.servers.view');
    Route::get('/external/{external_id}', 'Servers\ExternalServerController@index')->name('api.application.servers.external');

    Route::patch('/{server}/details', 'Servers\ServerDetailsController@details')->name('api.application.servers.details');
    Route::patch('/{server}/build', 'Servers\ServerDetailsController@build')->name('api.application.servers.build');
    Route::patch('/{server}/startup', 'Servers\StartupController@index')->name('api.application.servers.startup');

    Route::post('/', 'Servers\ServerController@store');
    Route::post('/{server}/suspend', 'Servers\ServerManagementController@suspend')->name('api.application.servers.suspend');
    Route::post('/{server}/unsuspend', 'Servers\ServerManagementController@unsuspend')->name('api.application.servers.unsuspend');
    Route::post('/{server}/reinstall', 'Servers\ServerManagementController@reinstall')->name('api.application.servers.reinstall');

    Route::delete('/{server}', 'Servers\ServerController@delete');
    Route::delete('/{server}/{force?}', 'Servers\ServerController@delete');

    // Database Management Endpoint
    Route::group(['prefix' => '/{server}/databases'], function () {
        Route::get('/', 'Servers\DatabaseController@index')->name('api.application.servers.databases');
        Route::get('/{database}', 'Servers\DatabaseController@view')->name('api.application.servers.databases.view');

        Route::post('/', 'Servers\DatabaseController@store');
        Route::post('/{database}/reset-password', 'Servers\DatabaseController@resetPassword');

        Route::delete('/{database}', 'Servers\DatabaseController@delete');
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
    Route::get('/', 'Users\UserController@index')->name('api.application.users');
    Route::get('/{user}', 'Users\UserController@view')->name('api.application.users.view');
    Route::get('/external/{external_id}', 'Users\ExternalUserController@index')->name('api.application.users.external');

    Route::post('/', 'Users\UserController@store');

    Route::patch('/{user}', 'Users\UserController@update');

    Route::delete('/{user}', 'Users\UserController@delete');
});
