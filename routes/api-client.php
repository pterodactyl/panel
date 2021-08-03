<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client
|
*/
Route::get('/', 'ClientController@index')->name('api:client.index');
Route::get('/permissions', 'ClientController@permissions');

Route::group(['prefix' => '/account'], function () {
    Route::get('/', 'AccountController@index')->name('api:client.account')->withoutMiddleware(RequireTwoFactorAuthentication::class);
    Route::get('/two-factor', 'TwoFactorController@index')->withoutMiddleware(RequireTwoFactorAuthentication::class);
    Route::post('/two-factor', 'TwoFactorController@store')->withoutMiddleware(RequireTwoFactorAuthentication::class);
    Route::delete('/two-factor', 'TwoFactorController@delete')->withoutMiddleware(RequireTwoFactorAuthentication::class);

    Route::put('/email', 'AccountController@updateEmail')->name('api:client.account.update-email');
    Route::put('/password', 'AccountController@updatePassword')->name('api:client.account.update-password');

    Route::get('/api-keys', 'ApiKeyController@index');
    Route::post('/api-keys', 'ApiKeyController@store');
    Route::delete('/api-keys/{identifier}', 'ApiKeyController@delete');
});

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client/servers/{server}
|
*/
Route::group(['prefix' => '/servers/{server}', 'middleware' => [AuthenticateServerAccess::class, ResourceBelongsToServer::class]], function () {
    Route::get('/', 'Servers\ServerController@index')->name('api:client:server.view');
    Route::get('/websocket', 'Servers\WebsocketController')->name('api:client:server.ws');
    Route::get('/resources', 'Servers\ResourceUtilizationController')->name('api:client:server.resources');

    Route::post('/command', 'Servers\CommandController@index');
    Route::post('/power', 'Servers\PowerController@index');

    Route::group(['prefix' => '/databases'], function () {
        Route::get('/', 'Servers\DatabaseController@index');
        Route::post('/', 'Servers\DatabaseController@store');
        Route::post('/{database}/rotate-password', 'Servers\DatabaseController@rotatePassword');
        Route::delete('/{database}', 'Servers\DatabaseController@delete');
    });

    Route::group(['prefix' => '/files'], function () {
        Route::get('/list', 'Servers\FileController@directory');
        Route::get('/contents', 'Servers\FileController@contents');
        Route::get('/download', 'Servers\FileController@download');
        Route::put('/rename', 'Servers\FileController@rename');
        Route::post('/copy', 'Servers\FileController@copy');
        Route::post('/write', 'Servers\FileController@write');
        Route::post('/compress', 'Servers\FileController@compress');
        Route::post('/decompress', 'Servers\FileController@decompress');
        Route::post('/delete', 'Servers\FileController@delete');
        Route::post('/create-folder', 'Servers\FileController@create');
        Route::post('/chmod', 'Servers\FileController@chmod');
        Route::post('/pull', 'Servers\FileController@pull')->middleware(['throttle:10,5']);
        Route::get('/upload', 'Servers\FileUploadController');
    });

    Route::group(['prefix' => '/schedules'], function () {
        Route::get('/', 'Servers\ScheduleController@index');
        Route::post('/', 'Servers\ScheduleController@store');
        Route::get('/{schedule}', 'Servers\ScheduleController@view');
        Route::post('/{schedule}', 'Servers\ScheduleController@update');
        Route::post('/{schedule}/execute', 'Servers\ScheduleController@execute');
        Route::delete('/{schedule}', 'Servers\ScheduleController@delete');

        Route::post('/{schedule}/tasks', 'Servers\ScheduleTaskController@store');
        Route::post('/{schedule}/tasks/{task}', 'Servers\ScheduleTaskController@update');
        Route::delete('/{schedule}/tasks/{task}', 'Servers\ScheduleTaskController@delete');
    });

    Route::group(['prefix' => '/network'], function () {
        Route::get('/allocations', 'Servers\NetworkAllocationController@index');
        Route::post('/allocations', 'Servers\NetworkAllocationController@store');
        Route::post('/allocations/{allocation}', 'Servers\NetworkAllocationController@update');
        Route::post('/allocations/{allocation}/primary', 'Servers\NetworkAllocationController@setPrimary');
        Route::delete('/allocations/{allocation}', 'Servers\NetworkAllocationController@delete');
    });

    Route::group(['prefix' => '/users'], function () {
        Route::get('/', 'Servers\SubuserController@index');
        Route::post('/', 'Servers\SubuserController@store');
        Route::get('/{user}', 'Servers\SubuserController@view');
        Route::post('/{user}', 'Servers\SubuserController@update');
        Route::delete('/{user}', 'Servers\SubuserController@delete');
    });

    Route::group(['prefix' => '/backups'], function () {
        Route::get('/', 'Servers\BackupController@index');
        Route::post('/', 'Servers\BackupController@store');
        Route::get('/{backup}', 'Servers\BackupController@view');
        Route::get('/{backup}/download', 'Servers\BackupController@download');
        Route::post('/{backup}/lock', 'Servers\BackupController@toggleLock');
        Route::post('/{backup}/restore', 'Servers\BackupController@restore');
        Route::delete('/{backup}', 'Servers\BackupController@delete');
    });

    Route::group(['prefix' => '/startup'], function () {
        Route::get('/', 'Servers\StartupController@index');
        Route::put('/variable', 'Servers\StartupController@update');
    });

    Route::group(['prefix' => '/settings'], function () {
        Route::post('/rename', 'Servers\SettingsController@rename');
        Route::post('/reinstall', 'Servers\SettingsController@reinstall');
        Route::put('/docker-image', 'Servers\SettingsController@dockerImage');
    });
});
