<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Wings Remote API Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/remote
|
*/
Route::post('/sftp/auth', 'SftpAuthenticationController');
Route::put('/sftp/auth', 'SftpAuthenticationController@getSSHKeys');

Route::get('/servers', 'Servers\ServerDetailsController@list');
Route::post('/servers/reset', 'Servers\ServerDetailsController@resetState');

Route::group(['prefix' => '/servers/{uuid}'], function () {
    Route::get('/', 'Servers\ServerDetailsController');
    Route::get('/install', 'Servers\ServerInstallController@index');
    Route::post('/install', 'Servers\ServerInstallController@store');

    Route::post('/archive', 'Servers\ServerTransferController@archive');
    Route::get('/transfer/failure', 'Servers\ServerTransferController@failure');
    Route::get('/transfer/success', 'Servers\ServerTransferController@success');
});

Route::group(['prefix' => '/backups'], function () {
    Route::get('/{backup}', 'Backups\BackupRemoteUploadController');
    Route::post('/{backup}', 'Backups\BackupStatusController@index');
    Route::post('/{backup}/restore', 'Backups\BackupStatusController@restore');
});
