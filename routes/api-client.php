<?php

use Pterodactyl\Http\Middleware\Api\Client\AuthenticateClientAccess;

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client
|
*/
Route::get('/', 'ClientController@index')->name('api.client.index');

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client/servers/{server}
|
*/
Route::group(['prefix' => '/servers/{server}', 'middleware' => [AuthenticateClientAccess::class]], function () {
    Route::get('/', 'Server\ServerController@index')->name('api.client.servers.view');

    Route::post('/command', 'Server\CommandController@index')->name('api.client.servers.command');
    Route::post('/power', 'Server\PowerController@index')->name('api.client.servers.power');
});
