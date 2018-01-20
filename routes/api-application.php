<?php

use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Allocation;

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/users
|
*/
Route::group(['prefix' => '/users'], function () {
    Route::bind('user', function ($value) {
        return User::find($value) ?? new User;
    });

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
    Route::bind('node', function ($value) {
        return Node::find($value) ?? new Node;
    });

    Route::get('/', 'Nodes\NodeController@index')->name('api.application.nodes');
    Route::get('/{node}', 'Nodes\NodeController@view')->name('api.application.nodes.view');

    Route::post('/', 'Nodes\NodeController@store');
    Route::patch('/{node}', 'Nodes\NodeController@update');

    Route::delete('/{node}', 'Nodes\NodeController@delete');

    Route::group(['prefix' => '/{node}/allocations'], function () {
        Route::bind('allocation', function ($value) {
            return Allocation::find($value) ?? new Allocation;
        });

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
    Route::bind('location', function ($value) {
        return Location::find($value) ?? new Location;
    });

    Route::get('/', 'Locations\LocationController@index')->name('api.applications.locations');
    Route::get('/{location}', 'Locations\LocationController@view')->name('api.application.locations.view');

    Route::post('/', 'Locations\LocationController@store');
    Route::patch('/{location}', 'Locations\LocationController@update');

    Route::delete('/{location}', 'Locations\LocationController@delete');
});

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/application/servers
|
*/
Route::group(['prefix' => '/servers'], function () {
    Route::bind('location', function ($value) {
        return Server::find($value) ?? new Location;
    });

    Route::get('/', 'Servers\ServerController@index')->name('api.application.servers');
    Route::get('/{server}', 'Servers\ServerController@view')->name('api.application.servers');
});
