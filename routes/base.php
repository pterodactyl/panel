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

    Route::post('/totp', 'SecurityController@setTotp')->name('account.security.totp.set');

    Route::delete('/totp', 'SecurityController@disableTotp')->name('account.security.totp.disable');
});

// Localization
$fallback_file = resource_path('lang/' . config('app.fallback_locale') . '/js.php');
$files = glob(resource_path('lang/*'));
foreach ($files as $file) {
    $lang = basename($file);

    Route::get('/js/lang/' . $lang . '.js', function () use ($fallback_file, $file, $lang) {
        $strings = Cache::remember('lang/' . $lang . '.js', 60, function () use ($fallback_file, $file, $lang) {
            $strings = [];
            if ($lang != config('app.fallback_locale')) {
                $strings['js'] = array_replace_recursive(require $fallback_file, require $file . '/js.php');
            } else {
                $strings['js'] = require $file . '/js.php';
            }

            return $strings;
        });

        header('Content-Type: text/javascript');
        echo 'window.i18n = ' . json_encode($strings) . ';';
        exit();
    })->name('assets.lang');
}
