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
Route::get('/', 'ServerController@getIndex')->name('server.index');

/*
|--------------------------------------------------------------------------
| Server Settings Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /server/{server}/settings
|
*/
Route::group(['prefix' => 'settings'], function () {
    Route::get('/databases', 'ServerController@getDatabases')->name('server.settings.databases');
    Route::get('/sftp', 'ServerController@getSFTP')->name('server.settings.sftp');
    Route::get('/startup', 'ServerController@getStartup')->name('server.settings.startup');
    Route::get('/allocation', 'ServerController@getAllocation')->name('server.settings.allocation');

    Route::post('/sftp', 'ServerController@postSettingsSFTP');
    Route::post('/startup', 'ServerController@postSettingsStartup');
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
    Route::get('/', 'ServerController@getFiles')->name('server.files.index');
    Route::get('/add', 'ServerController@getAddFile')->name('server.files.add');
    Route::get('/edit/{file}', 'ServerController@getEditFile')->name('server.files.edit');
    Route::get('/download/{file}', 'ServerController@getDownloadFile')
         ->name('server.files.edit')
         ->where('file', '.*');

    Route::post('/directory-list', 'AjaxController@postDirectoryList')->name('server.files.directory-list');
    Route::post('/save', 'AjaxController@postSaveFile')->name('server.files.save');
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
    Route::get('/view/{id}', 'SubuserController@view')->name('server.subusers.view');

    Route::post('/new', 'SubuserController@store');
    Route::post('/view/{id}', 'SubuserController@update');

    Route::delete('/delete/{id}', 'SubuserController@delete')->name('server.subusers.delete');
});

/*
|--------------------------------------------------------------------------
| Server Task Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /server/{server}/tasks
|
*/
Route::group(['prefix' => 'tasks'], function () {
    Route::get('/', 'TaskController@index')->name('server.tasks');
    Route::get('/new', 'TaskController@create')->name('server.tasks.new');

    Route::post('/new', 'TaskController@store');
    Route::post('/toggle/{id}', 'TaskController@toggle')->name('server.tasks.toggle');

    Route::delete('/delete/{id}', 'TaskController@delete')->name('server.tasks.delete');
});

/*
|--------------------------------------------------------------------------
| Server Ajax Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /server/{server}/ajax
|
*/
Route::group(['prefix' => 'ajax'], function () {
    Route::get('/status', 'AjaxController@getStatus')->name('server.ajax.status');

    Route::post('/set-primary', 'AjaxController@postSetPrimary')->name('server.ajax.set-primary');
    Route::post('/settings/reset-database-password', 'AjaxController@postResetDatabasePassword')->name('server.ajax.reset-database-password');
});
