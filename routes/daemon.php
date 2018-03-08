<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
Route::get('/packs/pull/{uuid}', 'PackController@pull')->name('daemon.pack.pull');
Route::get('/packs/pull/{uuid}/hash', 'PackController@hash')->name('daemon.pack.hash');
Route::get('/configure/{token}', 'ActionController@configuration')->name('daemon.configuration');

Route::post('/install', 'ActionController@markInstall')->name('daemon.install');
