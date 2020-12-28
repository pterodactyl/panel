<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'BaseController@index')->name('admin.index')->fallback();
Route::get('/{react}', 'BaseController@index')
    ->where('react', '.+');

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin/api
|
*/
Route::group(['prefix' => 'api'], function () {
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
