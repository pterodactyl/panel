<?php

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
    Route::get('/{user}', 'Users\UserController@view')->name('api.applications.users.view');

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
    Route::get('/{node}', 'Nodes\NodeController@view')->name('api.application.nodes.view');

    Route::post('/', 'Nodes\NodeController@store');
    Route::patch('/{node}', 'Nodes\NodeController@update');

    Route::delete('/{node}', 'Nodes\NodeController@delete');

    Route::group(['prefix' => '/{node}/allocations'], function () {
        Route::get('/', 'Nodes\AllocationController@index')->name('api.application.allocations');

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

    Route::post('/{server}/suspend', 'Servers\ServerManagementController@suspend')->name('api.application.servers.suspend');
    Route::post('/{server}/unsuspend', 'Servers\ServerManagementController@unsuspend')->name('api.application.servers.unsuspend');
    Route::post('/{server}/reinstall', 'Servers\ServerManagementController@reinstall')->name('api.application.servers.reinstall');
    Route::post('/{server}/rebuild', 'Servers\ServerManagementController@rebuild')->name('api.application.servers.rebuild');

    Route::delete('/{server}', 'Servers\ServerController@delete');
    Route::delete('/{server}/{force?}', 'Servers\ServerController@delete');
});
