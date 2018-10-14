<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
Route::get('/credentials', 'CredentialsController@index')->name('server.credentials');

Route::group(['prefix' => '/files'], function () {
    Route::get('/{directory?}', 'FileController@index')
        ->name('server.files')
        ->where('directory', '.*');
});

Route::get('/')->name('server.index');
Route::get('/console')->name('server.console');
