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
| Endpoint: /deploy
|
*/
Route::group(['prefix' => 'deploy'], function () {
    Route::get('/', 'DeployController@index')->name('deploy');
    Route::post('/', 'DeployController@submit')->name('deploy.submit');
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


/*
|--------------------------------------------------------------------------
| Account Billing Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /account/billing
|
*/
Route::group(['prefix' => 'account/billing'], function () {
    Route::get('/', 'BillingController@index')->name('account.billing');
    Route::get('/invoice/pdf/{id}', 'BillingController@invoicePdf')->name('account.invoice.pdf');
    Route::post('/paypal', 'BillingController@paypal')->name('account.billing.paypal');
    Route::get('/paypal/callback', 'BillingController@paypalCallback')->name('account.billing.paypal.callback');
    Route::post('/link', 'BillingController@link')->name('account.billing.link');
    Route::post('/unlink', 'BillingController@unlink')->name('account.billing.unlink');
    Route::post('/billing', 'BillingController@billing')->name('account.billing.info');
});
