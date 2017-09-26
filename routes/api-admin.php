<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
Route::get('/', '\Pterodactyl\Http\Controllers\API\User\CoreController@index');

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/servers
|
*/
Route::group(['prefix' => '/servers'], function () {
    Route::get('/', 'ServerController@index');
    Route::get('/{id}', 'ServerController@view');

    Route::post('/', 'ServerController@store');

    Route::put('/{id}/details', 'ServerController@details');
    Route::put('/{id}/container', 'ServerController@container');
    Route::put('/{id}/build', 'ServerController@build');
    Route::put('/{id}/startup', 'ServerController@startup');

    Route::patch('/{id}/install', 'ServerController@install');
    Route::patch('/{id}/rebuild', 'ServerController@rebuild');
    Route::patch('/{id}/suspend', 'ServerController@suspend');

    Route::delete('/{id}', 'ServerController@delete');
});

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/locations
|
*/
Route::group(['prefix' => '/locations'], function () {
    Route::get('/', 'LocationController@index');
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/nodes
|
*/
Route::group(['prefix' => '/nodes'], function () {
    Route::get('/', 'NodeController@index');
    Route::get('/{id}', 'NodeController@view');
    Route::get('/{id}/config', 'NodeController@viewConfig');

    Route::post('/', 'NodeController@store');

    Route::delete('/{id}', 'NodeController@delete');
});

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/users
|
*/
Route::group(['prefix' => '/users'], function () {
    Route::get('/', 'UserController@index');
    Route::get('/{id}', 'UserController@view');

    Route::post('/', 'UserController@store');

    Route::put('/{id}', 'UserController@update');

    Route::delete('/{id}', 'UserController@delete');
});

/*
|--------------------------------------------------------------------------
| Service Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/services
|
*/
Route::group(['prefix' => '/services'], function () {
    Route::get('/', 'ServiceController@index');
    Route::get('/{id}', 'ServiceController@view');
});
