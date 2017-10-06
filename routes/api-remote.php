<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
Route::get('/authenticate/{token}', 'ValidateKeyController@index')->name('api.remote.authenticate');

Route::group(['prefix' => '/options'], function () {
    Route::get('/', 'OptionRetrievalController@index')->name('api.remote.services');
    Route::get('/{uuid}', 'OptionRetrievalController@download')->name('api.remote.services.download');
});
