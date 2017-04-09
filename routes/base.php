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

Route::get('/', 'IndexController@getIndex')->name('index');
Route::get('/index', function () {
    redirect()->route('index');
});

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
    Route::get('/', 'APIController@index')->name('account.api');
    Route::get('/new', 'APIController@create')->name('account.api.new');

    Route::post('/new', 'APIController@store');

    Route::delete('/revoke/{key}', 'APIController@revoke')->name('account.api.revoke');
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

    Route::post('/totp', 'SecurityController@setTotp');

    Route::delete('/api/security/totp', 'SecurityController@disableTotp');
});
