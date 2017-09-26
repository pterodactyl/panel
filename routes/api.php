<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
Route::get('/', 'CoreController@index')->name('api.user');

/*
|--------------------------------------------------------------------------
| Server Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/user/server/{server}
|
*/
Route::group([
    'prefix' => '/server/{server}',
    'middleware' => 'server',
], function () {
    Route::get('/', 'ServerController@index')->name('api.user.server');

    Route::post('/power', 'ServerController@power')->name('api.user.server.power');
    Route::post('/command', 'ServerController@command')->name('api.user.server.command');
});
