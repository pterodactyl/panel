<?php

use Illuminate\Support\Facades\Route;

// Routes for the Wings daemon.
Route::post('/sftp/auth', 'SftpAuthenticationController');

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
