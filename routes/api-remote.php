<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
Route::get('/authenticate/{token}', 'ValidateKeyController@index')->name('api.remote.authenticate');

Route::group(['prefix' => '/eggs'], function () {
    Route::get('/', 'EggRetrievalController@download@index')->name('api.remote.eggs');
    Route::get('/{uuid}', 'EggRetrievalController@download')->name('api.remote.eggs.download');
});
