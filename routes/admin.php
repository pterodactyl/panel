<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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
    Route::patch('/view/{location}', 'LocationController@update');
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
    Route::get('/view/{server}', 'ServersController@viewIndex')->name('admin.servers.view');
    Route::get('/view/{server}/details', 'ServersController@viewDetails')->name('admin.servers.view.details');
    Route::get('/view/{server}/build', 'ServersController@viewBuild')->name('admin.servers.view.build');
    Route::get('/view/{server}/startup', 'ServersController@viewStartup')->name('admin.servers.view.startup');
    Route::get('/view/{server}/database', 'ServersController@viewDatabase')->name('admin.servers.view.database');
    Route::get('/view/{server}/manage', 'ServersController@viewManage')->name('admin.servers.view.manage');
    Route::get('/view/{server}/delete', 'ServersController@viewDelete')->name('admin.servers.view.delete');

    Route::post('/new', 'ServersController@store');
    Route::post('/new/nodes', 'ServersController@nodes')->name('admin.servers.new.nodes');
    Route::post('/view/{server}/build', 'ServersController@updateBuild');
    Route::post('/view/{server}/startup', 'ServersController@saveStartup');
    Route::post('/view/{server}/database', 'ServersController@newDatabase');
    Route::post('/view/{server}/manage/toggle', 'ServersController@toggleInstall')->name('admin.servers.view.manage.toggle');
    Route::post('/view/{server}/manage/rebuild', 'ServersController@rebuildContainer')->name('admin.servers.view.manage.rebuild');
    Route::post('/view/{server}/manage/suspension', 'ServersController@manageSuspension')->name('admin.servers.view.manage.suspension');
    Route::post('/view/{server}/manage/reinstall', 'ServersController@reinstallServer')->name('admin.servers.view.manage.reinstall');
    Route::post('/view/{server}/delete', 'ServersController@delete');

    Route::patch('/view/{server}/details', 'ServersController@setDetails');
    Route::patch('/view/{server}/details/container', 'ServersController@setContainer')->name('admin.servers.view.details.container');
    Route::patch('/view/{server}/database', 'ServersController@resetDatabasePassword');

    Route::delete('/view/{server}/database/{database}/delete', 'ServersController@deleteDatabase')->name('admin.servers.view.database.delete');
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
    Route::get('/view/{node}', 'NodesController@viewIndex')->name('admin.nodes.view');
    Route::get('/view/{node}/settings', 'NodesController@viewSettings')->name('admin.nodes.view.settings');
    Route::get('/view/{node}/configuration', 'NodesController@viewConfiguration')->name('admin.nodes.view.configuration');
    Route::get('/view/{node}/allocation', 'NodesController@viewAllocation')->name('admin.nodes.view.allocation');
    Route::get('/view/{node}/servers', 'NodesController@viewServers')->name('admin.nodes.view.servers');
    Route::get('/view/{node}/settings/token', 'NodesController@setToken')->name('admin.nodes.view.configuration.token');

    Route::post('/new', 'NodesController@store');
    Route::post('/view/{node}/allocation', 'NodesController@createAllocation');
    Route::post('/view/{node}/allocation/remove', 'NodesController@allocationRemoveBlock')->name('admin.nodes.view.allocation.removeBlock');
    Route::post('/view/{node}/allocation/alias', 'NodesController@allocationSetAlias')->name('admin.nodes.view.allocation.setAlias');

    Route::patch('/view/{node}/settings', 'NodesController@updateSettings');

    Route::delete('/view/{node}/delete', 'NodesController@delete')->name('admin.nodes.view.delete');
    Route::delete('/view/{node}/allocation/remove/{allocation}', 'NodesController@allocationRemoveSingle')->name('admin.nodes.view.allocation.removeSingle');
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
    Route::get('/view/{service}', 'ServiceController@view')->name('admin.services.view');
    Route::get('/view/{service}/functions', 'ServiceController@viewFunctions')->name('admin.services.view.functions');
    Route::get('/option/new', 'OptionController@create')->name('admin.services.option.new');
    Route::get('/option/{option}', 'OptionController@viewConfiguration')->name('admin.services.option.view');
    Route::get('/option/{option}/export', 'Services\Options\OptionShareController@export')->name('admin.services.option.export');
    Route::get('/option/{option}/variables', 'VariableController@view')->name('admin.services.option.variables');
    Route::get('/option/{option}/scripts', 'OptionController@viewScripts')->name('admin.services.option.scripts');

    Route::post('/new', 'ServiceController@store');
    Route::post('/import', 'Services\Options\OptionShareController@import')->name('admin.services.option.import');
    Route::post('/option/new', 'OptionController@store');
    Route::post('/option/{option}/variables', 'VariableController@store');

    Route::patch('/view/{service}', 'ServiceController@update');
    Route::patch('/view/{service}/functions', 'ServiceController@updateFunctions');
    Route::patch('/option/{option}', 'OptionController@editConfiguration');
    Route::patch('/option/{option}/scripts', 'OptionController@updateScripts');
    Route::patch('/option/{option}/variables/{variable}', 'VariableController@update')->name('admin.services.option.variables.edit');

    Route::delete('/view/{service}', 'ServiceController@destroy');
    Route::delete('/option/{option}', 'OptionController@destroy');
    Route::delete('/option/{option}/variables/{variable}', 'VariableController@delete');
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
    Route::get('/view/{pack}', 'PackController@view')->name('admin.packs.view');

    Route::post('/new', 'PackController@store');
    Route::post('/view/{pack}/export/{files?}', 'PackController@export')->name('admin.packs.view.export');

    Route::patch('/view/{pack}', 'PackController@update');

    Route::delete('/view/{pack}', 'PackController@destroy');
});
