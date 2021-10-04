<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Middleware\Admin\Servers\ServerInstalled;

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
    Route::get('/mail/test', 'Settings\MailController@test')->name('admin.settings.mail.test');
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
    Route::get('/', 'Servers\ServerController@index')->name('admin.servers');
    Route::get('/new', 'Servers\CreateServerController@index')->name('admin.servers.new');
    Route::get('/view/{server}', 'Servers\ServerViewController@index')->name('admin.servers.view');

    Route::group(['middleware' => [ServerInstalled::class]], function () {
        Route::get('/view/{server}/details', 'Servers\ServerViewController@details')->name('admin.servers.view.details');
        Route::get('/view/{server}/build', 'Servers\ServerViewController@build')->name('admin.servers.view.build');
        Route::get('/view/{server}/startup', 'Servers\ServerViewController@startup')->name('admin.servers.view.startup');
        Route::get('/view/{server}/database', 'Servers\ServerViewController@database')->name('admin.servers.view.database');
        Route::get('/view/{server}/mounts', 'Servers\ServerViewController@mounts')->name('admin.servers.view.mounts');
    });

    Route::get('/view/{server}/manage', 'Servers\ServerViewController@manage')->name('admin.servers.view.manage');
    Route::get('/view/{server}/delete', 'Servers\ServerViewController@delete')->name('admin.servers.view.delete');

    Route::post('/new', 'Servers\CreateServerController@store');
    Route::post('/view/{server}/build', 'ServersController@updateBuild');
    Route::post('/view/{server}/startup', 'ServersController@saveStartup');
    Route::post('/view/{server}/database', 'ServersController@newDatabase');
    Route::post('/view/{server}/mounts/{mount}', 'ServersController@addMount')->name('admin.servers.view.mounts.toggle');
    Route::post('/view/{server}/manage/toggle', 'ServersController@toggleInstall')->name('admin.servers.view.manage.toggle');
    Route::post('/view/{server}/manage/suspension', 'ServersController@manageSuspension')->name('admin.servers.view.manage.suspension');
    Route::post('/view/{server}/manage/reinstall', 'ServersController@reinstallServer')->name('admin.servers.view.manage.reinstall');
    Route::post('/view/{server}/manage/transfer', 'Servers\ServerTransferController@transfer')->name('admin.servers.view.manage.transfer');
    Route::post('/view/{server}/delete', 'ServersController@delete');

    Route::patch('/view/{server}/details', 'ServersController@setDetails');
    Route::patch('/view/{server}/database', 'ServersController@resetDatabasePassword');

    Route::delete('/view/{server}/database/{database}/delete', 'ServersController@deleteDatabase')->name('admin.servers.view.database.delete');
    Route::delete('/view/{server}/mounts/{mount}', 'ServersController@deleteMount');
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
    Route::get('/', 'Nodes\NodeController@index')->name('admin.nodes');
    Route::get('/new', 'NodesController@create')->name('admin.nodes.new');
    Route::get('/view/{node}', 'Nodes\NodeViewController@index')->name('admin.nodes.view');
    Route::get('/view/{node}/settings', 'Nodes\NodeViewController@settings')->name('admin.nodes.view.settings');
    Route::get('/view/{node}/configuration', 'Nodes\NodeViewController@configuration')->name('admin.nodes.view.configuration');
    Route::get('/view/{node}/allocation', 'Nodes\NodeViewController@allocations')->name('admin.nodes.view.allocation');
    Route::get('/view/{node}/servers', 'Nodes\NodeViewController@servers')->name('admin.nodes.view.servers');
    Route::get('/view/{node}/system-information', 'Nodes\SystemInformationController');
    Route::get('/view/{node}/settings/token', 'NodeAutoDeployController')->name('admin.nodes.view.configuration.token');

    Route::post('/new', 'NodesController@store');
    Route::post('/view/{node}/allocation', 'NodesController@createAllocation');
    Route::post('/view/{node}/allocation/remove', 'NodesController@allocationRemoveBlock')->name('admin.nodes.view.allocation.removeBlock');
    Route::post('/view/{node}/allocation/alias', 'NodesController@allocationSetAlias')->name('admin.nodes.view.allocation.setAlias');

    Route::patch('/view/{node}/settings', 'NodesController@updateSettings');

    Route::delete('/view/{node}/delete', 'NodesController@delete')->name('admin.nodes.view.delete');
    Route::delete('/view/{node}/allocation/remove/{allocation}', 'NodesController@allocationRemoveSingle')->name('admin.nodes.view.allocation.removeSingle');
    Route::delete('/view/{node}/allocations', 'NodesController@allocationRemoveMultiple')->name('admin.nodes.view.allocation.removeMultiple');
});

/*
|--------------------------------------------------------------------------
| Mount Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/mounts
|
*/
Route::group(['prefix' => 'mounts'], function () {
    Route::get('/', 'MountController@index')->name('admin.mounts');
    Route::get('/view/{mount}', 'MountController@view')->name('admin.mounts.view');

    Route::post('/', 'MountController@create');
    Route::post('/{mount}/eggs', 'MountController@addEggs')->name('admin.mounts.eggs');
    Route::post('/{mount}/nodes', 'MountController@addNodes')->name('admin.mounts.nodes');

    Route::patch('/view/{mount}', 'MountController@update');

    Route::delete('/{mount}/eggs/{egg_id}', 'MountController@deleteEgg');
    Route::delete('/{mount}/nodes/{node_id}', 'MountController@deleteNode');
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
