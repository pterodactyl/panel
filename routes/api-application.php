<?php

use Illuminate\Support\Facades\Route;

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
| Nest Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/nests
|
*/
Route::group(['prefix' => '/nests'], function () {
    Route::get('/', 'Nests\NestController@index')->name('api.application.nests');
    Route::get('/{nest}', 'Nests\NestController@view')->name('api.application.nests.view');

    // Egg Management Endpoint
    Route::group(['prefix' => '/{nest}/eggs'], function () {
        Route::get('/', 'Nests\EggController@index')->name('api.application.nests.eggs');
        Route::get('/{egg}', 'Nests\EggController@view')->name('api.application.nests.eggs.view');
    });
});
