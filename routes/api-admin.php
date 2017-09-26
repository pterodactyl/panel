<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
