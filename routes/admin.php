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
Route::get('/', 'BaseController@getIndex')->name('admin.index');

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/locations
|
*/
Route::group(['prefix' => 'locations'], function () {
    Route::get('/', 'LocationController@index')->name('admin.locations');
    Route::get('/view/{location}', 'LocationController@view')->name('admin.locations.view');

    Route::post('/', 'LocationController@create');
    Route::post('/view/{location}', 'LocationController@update');
});

/*
|--------------------------------------------------------------------------
| Database Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/databases
|
*/
Route::group(['prefix' => 'databases'], function () {
    Route::get('/', 'DatabaseController@index')->name('admin.databases');
    Route::get('/view/{host}', 'DatabaseController@view')->name('admin.databases.view');

    Route::post('/', 'DatabaseController@create');
    Route::patch('/view/{host}', 'DatabaseController@update');
});

/*
|--------------------------------------------------------------------------
| Settings Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/settings
|
*/
Route::group(['prefix' => 'settings'], function () {
    Route::get('/', 'BaseController@getSettings')->name('admin.settings');

    Route::post('/', 'BaseController@postSettings');
});

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/users
|
*/
Route::group(['prefix' => 'users'], function () {
    Route::get('/', 'UserController@index')->name('admin.users');
    Route::get('/accounts.json', 'UserController@json')->name('admin.users.json');
    Route::get('/new', 'UserController@create')->name('admin.users.new');
    Route::get('/view/{user}', 'UserController@view')->name('admin.users.view');

    Route::post('/new', 'UserController@store');
    Route::patch('/view/{user}', 'UserController@update');

    Route::delete('/view/{user}', 'UserController@delete');
});

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/servers
|
*/
Route::group(['prefix' => 'servers'], function () {
    Route::get('/', 'ServersController@index')->name('admin.servers');
    Route::get('/new', 'ServersController@create')->name('admin.servers.new');
    Route::get('/view/{id}', 'ServersController@viewIndex')->name('admin.servers.view');
    Route::get('/view/{id}/details', 'ServersController@viewDetails')->name('admin.servers.view.details');
    Route::get('/view/{id}/build', 'ServersController@viewBuild')->name('admin.servers.view.build');
    Route::get('/view/{id}/startup', 'ServersController@viewStartup')->name('admin.servers.view.startup');
    Route::get('/view/{id}/database', 'ServersController@viewDatabase')->name('admin.servers.view.database');
    Route::get('/view/{id}/manage', 'ServersController@viewManage')->name('admin.servers.view.manage');
    Route::get('/view/{id}/delete', 'ServersController@viewDelete')->name('admin.servers.view.delete');

    Route::post('/new', 'ServersController@store');
    Route::post('/new/nodes', 'ServersController@nodes')->name('admin.servers.new.nodes');
    Route::post('/view/{id}/details', 'ServersController@setDetails');
    Route::post('/view/{id}/details/container', 'ServersController@setContainer')->name('admin.servers.view.details.container');
    Route::post('/view/{id}/build', 'ServersController@updateBuild');
    Route::post('/view/{id}/startup', 'ServersController@saveStartup');
    Route::post('/view/{id}/database', 'ServersController@newDatabase');
    Route::post('/view/{id}/manage/toggle', 'ServersController@toggleInstall')->name('admin.servers.view.manage.toggle');
    Route::post('/view/{id}/manage/rebuild', 'ServersController@rebuildContainer')->name('admin.servers.view.manage.rebuild');
    Route::post('/view/{id}/manage/suspension', 'ServersController@manageSuspension')->name('admin.servers.view.manage.suspension');
    Route::post('/view/{id}/manage/reinstall', 'ServersController@reinstallServer')->name('admin.servers.view.manage.reinstall');
    Route::post('/view/{id}/delete', 'ServersController@delete');

    Route::patch('/view/{id}/database', 'ServersController@resetDatabasePassword');

    Route::delete('/view/{id}/database/{database}/delete', 'ServersController@deleteDatabase')->name('admin.servers.view.database.delete');
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nodes
|
*/
Route::group(['prefix' => 'nodes'], function () {
    Route::get('/', 'NodesController@index')->name('admin.nodes');
    Route::get('/new', 'NodesController@create')->name('admin.nodes.new');
    Route::get('/view/{id}', 'NodesController@viewIndex')->name('admin.nodes.view');
    Route::get('/view/{id}/settings', 'NodesController@viewSettings')->name('admin.nodes.view.settings');
    Route::get('/view/{id}/configuration', 'NodesController@viewConfiguration')->name('admin.nodes.view.configuration');
    Route::get('/view/{id}/allocation', 'NodesController@viewAllocation')->name('admin.nodes.view.allocation');
    Route::get('/view/{id}/servers', 'NodesController@viewServers')->name('admin.nodes.view.servers');
    Route::get('/view/{id}/settings/token', 'NodesController@setToken')->name('admin.nodes.view.configuration.token');

    Route::post('/new', 'NodesController@store');
    Route::post('/view/{id}/settings', 'NodesController@updateSettings');
    Route::post('/view/{id}/allocation', 'NodesController@createAllocation');
    Route::post('/view/{id}/allocation/remove', 'NodesController@allocationRemoveBlock')->name('admin.nodes.view.allocation.removeBlock');
    Route::post('/view/{id}/allocation/alias', 'NodesController@allocationSetAlias')->name('admin.nodes.view.allocation.setAlias');

    Route::delete('/view/{id}/delete', 'NodesController@delete')->name('admin.nodes.view.delete');
    Route::delete('/view/{id}/allocation/remove/{allocation}', 'NodesController@allocationRemoveSingle')->name('admin.nodes.view.allocation.removeSingle');
});

/*
|--------------------------------------------------------------------------
| Service Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/services
|
*/
Route::group(['prefix' => 'services'], function () {
    Route::get('/', 'ServiceController@index')->name('admin.services');
    Route::get('/new', 'ServiceController@create')->name('admin.services.new');
    Route::get('/view/{id}', 'ServiceController@view')->name('admin.services.view');
    Route::get('/view/{id}/functions', 'ServiceController@viewFunctions')->name('admin.services.view.functions');
    Route::get('/option/new', 'OptionController@create')->name('admin.services.option.new');
    Route::get('/option/{option}', 'OptionController@viewConfiguration')->name('admin.services.option.view');
    Route::get('/option/{option}/variables', 'OptionController@viewVariables')->name('admin.services.option.variables');
    Route::get('/option/{option}/scripts', 'OptionController@viewScripts')->name('admin.services.option.scripts');

    Route::post('/new', 'ServiceController@store');
    Route::post('/view/{option}', 'ServiceController@edit');
    Route::post('/option/new', 'OptionController@store');
    Route::post('/option/{option}', 'OptionController@editConfiguration');
    Route::post('/option/{option}/scripts', 'OptionController@updateScripts');
    Route::post('/option/{option}/variables', 'OptionController@createVariable');
    Route::post('/option/{option}/variables/{variable}', 'OptionController@editVariable')->name('admin.services.option.variables.edit');

    Route::delete('/view/{id}', 'ServiceController@delete');
});

/*
|--------------------------------------------------------------------------
| Pack Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/packs
|
*/
Route::group(['prefix' => 'packs'], function () {
    Route::get('/', 'PackController@index')->name('admin.packs');
    Route::get('/new', 'PackController@create')->name('admin.packs.new');
    Route::get('/new/template', 'PackController@newTemplate')->name('admin.packs.new.template');
    Route::get('/view/{id}', 'PackController@view')->name('admin.packs.view');

    Route::post('/new', 'PackController@store');
    Route::post('/view/{id}', 'PackController@update');
    Route::post('/view/{id}/export/{files?}', 'PackController@export')->name('admin.packs.view.export');
});
