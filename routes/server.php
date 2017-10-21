<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
Route::get('/', 'ConsoleController@index')->name('server.index');
Route::get('/console', 'ConsoleController@console')->name('server.console');

/*
|--------------------------------------------------------------------------
| Server Settings Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /server/{server}/settings
|
*/
Route::group(['prefix' => 'settings'], function () {
    Route::get('/allocation', 'Settings\AllocationController@index')->name('server.settings.allocation');
    Route::patch('/allocation', 'Settings\AllocationController@update');

    Route::get('/sftp', 'ServerController@getSFTP')->name('server.settings.sftp');
    Route::get('/startup', 'ServerController@getStartup')->name('server.settings.startup');

    Route::post('/sftp', 'ServerController@postSettingsSFTP');
    Route::post('/startup', 'ServerController@postSettingsStartup');
});

/*
|--------------------------------------------------------------------------
| Server Database Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /server/{server}/databases
|
*/
Route::group(['prefix' => 'databases'], function () {
    Route::get('/', 'DatabaseController@index')->name('server.databases.index');

    Route::patch('/password', 'DatabaseController@update')->middleware('server..database')->name('server.databases.password');
});

/*
|--------------------------------------------------------------------------
| Server File Manager Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /server/{server}/files
|
*/
Route::group(['prefix' => 'files'], function () {
    Route::get('/', 'Files\FileActionsController@index')->name('server.files.index');
    Route::get('/add', 'Files\FileActionsController@create')->name('server.files.add');
    Route::get('/edit/{file}', 'Files\FileActionsController@update')->name('server.files.edit')->where('file', '.*');
    Route::get('/download/{file}', 'Files\DownloadController@index')->name('server.files.edit')->where('file', '.*');

    Route::post('/directory-list', 'Files\RemoteRequestController@directory')->name('server.files.directory-list');
    Route::post('/save', 'Files\RemoteRequestController@store')->name('server.files.save');
});

/*
|--------------------------------------------------------------------------
| Server Subuser Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /server/{server}/users
|
*/
Route::group(['prefix' => 'users'], function () {
    Route::get('/', 'SubuserController@index')->name('server.subusers');
    Route::get('/new', 'SubuserController@create')->name('server.subusers.new');
    Route::get('/view/{subuser}', 'SubuserController@view')->middleware('server..subuser')->name('server.subusers.view');

    Route::post('/new', 'SubuserController@store');

    Route::patch('/view/{subuser}', 'SubuserController@update')->middleware('server..subuser');

    Route::delete('/view/{subuser}/delete', 'SubuserController@delete')->middleware('server..subuser')->name('server.subusers.delete');
});

/*
|--------------------------------------------------------------------------
| Server Task Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /server/{server}/tasks
|
*/
Route::group(['prefix' => 'schedules'], function () {
    Route::get('/', 'Tasks\TaskManagementController@index')->name('server.schedules');
    Route::get('/new', 'Tasks\TaskManagementController@create')->name('server.schedules.new');
    Route::get('/view/{schedule}', 'Tasks\TaskManagementController@view')->middleware('server..schedule')->name('server.schedules.view');

    Route::post('/new', 'Tasks\TaskManagementController@store');

    Route::patch('/view/{schedule}', 'Tasks\TaskManagementController@update')->middleware('server..schedule');
    Route::patch('/view/{schedule}/toggle', 'Tasks\TaskToggleController@index')->middleware('server..schedule')->name('server.schedules.toggle');

    Route::delete('/view/{schedule}/delete', 'Tasks\TaskManagementController@delete')->middleware('server..schedule')->name('server.schedules.delete');
});
