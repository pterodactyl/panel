<?php

Route::get('/', 'BaseController@index')->name('admin.index');

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/api
|
*/
Route::group(['prefix' => 'api'], function () {
    Route::get('/', 'ApiController@index')->name('admin.api.index');
    Route::get('/new', 'ApiController@create')->name('admin.api.new');

    Route::post('/new', 'ApiController@store');

    Route::delete('/revoke/{identifier}', 'ApiController@delete')->name('admin.api.delete');
});

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
    Route::delete('/view/{host}', 'DatabaseController@delete');
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
    Route::get('/', 'Settings\IndexController@index')->name('admin.settings');
    Route::get('/mail', 'Settings\MailController@index')->name('admin.settings.mail');
    Route::get('/advanced', 'Settings\AdvancedController@index')->name('admin.settings.advanced');

    Route::patch('/', 'Settings\IndexController@update');
    Route::patch('/mail', 'Settings\MailController@update');
    Route::patch('/advanced', 'Settings\AdvancedController@update');
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
    Route::post('/view/{server}/build', 'ServersController@updateBuild');
    Route::post('/view/{server}/startup', 'ServersController@saveStartup');
    Route::post('/view/{server}/database', 'ServersController@newDatabase');
    Route::post('/view/{server}/manage/toggle', 'ServersController@toggleInstall')->name('admin.servers.view.manage.toggle');
    Route::post('/view/{server}/manage/rebuild', 'ServersController@rebuildContainer')->name('admin.servers.view.manage.rebuild');
    Route::post('/view/{server}/manage/suspension', 'ServersController@manageSuspension')->name('admin.servers.view.manage.suspension');
    Route::post('/view/{server}/manage/reinstall', 'ServersController@reinstallServer')->name('admin.servers.view.manage.reinstall');
    Route::post('/view/{server}/delete', 'ServersController@delete');

    Route::patch('/view/{server}/details', 'ServersController@setDetails');
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
| Nest Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/nests
|
*/
Route::group(['prefix' => 'nests'], function () {
    Route::get('/', 'Nests\NestController@index')->name('admin.nests');
    Route::get('/new', 'Nests\NestController@create')->name('admin.nests.new');
    Route::get('/view/{nest}', 'Nests\NestController@view')->name('admin.nests.view');
    Route::get('/egg/new', 'Nests\EggController@create')->name('admin.nests.egg.new');
    Route::get('/egg/{egg}', 'Nests\EggController@view')->name('admin.nests.egg.view');
    Route::get('/egg/{egg}/export', 'Nests\EggShareController@export')->name('admin.nests.egg.export');
    Route::get('/egg/{egg}/variables', 'Nests\EggVariableController@view')->name('admin.nests.egg.variables');
    Route::get('/egg/{egg}/scripts', 'Nests\EggScriptController@index')->name('admin.nests.egg.scripts');

    Route::post('/new', 'Nests\NestController@store');
    Route::post('/import', 'Nests\EggShareController@import')->name('admin.nests.egg.import');
    Route::post('/egg/new', 'Nests\EggController@store');
    Route::post('/egg/{egg}/variables', 'Nests\EggVariableController@store');

    Route::put('/egg/{egg}', 'Nests\EggShareController@update');

    Route::patch('/view/{nest}', 'Nests\NestController@update');
    Route::patch('/egg/{egg}', 'Nests\EggController@update');
    Route::patch('/egg/{egg}/scripts', 'Nests\EggScriptController@update');
    Route::patch('/egg/{egg}/variables/{variable}', 'Nests\EggVariableController@update')->name('admin.nests.egg.variables.edit');

    Route::delete('/view/{nest}', 'Nests\NestController@destroy');
    Route::delete('/egg/{egg}', 'Nests\EggController@destroy');
    Route::delete('/egg/{egg}/variables/{variable}', 'Nests\EggVariableController@destroy');
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
