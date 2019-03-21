<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
Route::get('/', 'IndexController@getIndex')->name('index');
Route::get('/status/{server}', 'IndexController@status')->name('index.status');

/*
|--------------------------------------------------------------------------
| Account Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /account
|
*/
Route::group(['prefix' => 'account'], function () {
    Route::get('/', 'AccountController@index')->name('account');

    Route::post('/', 'AccountController@update');
});

/*
|--------------------------------------------------------------------------
| Account API Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /account/api
|
*/
Route::group(['prefix' => 'account/api'], function () {
    Route::get('/', 'ClientApiController@index')->name('account.api');
    Route::get('/new', 'ClientApiController@create')->name('account.api.new');

    Route::post('/new', 'ClientApiController@store');

    Route::delete('/revoke/{identifier}', 'ClientApiController@delete')->name('account.api.revoke');
});

/*
|--------------------------------------------------------------------------
| Account Security Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /account/security
|
*/
Route::group(['prefix' => 'account/security'], function () {
    Route::get('/', 'SecurityController@index')->name('account.security');
    Route::get('/revoke/{id}', 'SecurityController@revoke')->name('account.security.revoke');

    Route::put('/totp', 'SecurityController@generateTotp')->name('account.security.totp');

    Route::post('/totp', 'SecurityController@setTotp')->name('account.security.totp.set');

    Route::delete('/totp', 'SecurityController@disableTotp')->name('account.security.totp.disable');
});
