<?php

use Illuminate\Support\Facades\Route;

Route::get('/authenticate/{token}', 'ValidateKeyController@index');
Route::post('/download-file', 'FileDownloadController@index');

// Routes for the Wings daemon.
Route::post('/sftp/auth', 'SftpAuthenticationController');
Route::group(['prefix' => '/servers/{uuid}'], function () {
    Route::get('/', 'Servers\ServerDetailsController');
    Route::get('/install', 'Servers\ServerInstallController@index');
    Route::post('/install', 'Servers\ServerInstallController@store');
    Route::post('/archive', 'Servers\ServerTransferController@archive');
    Route::get('/transfer/failure', 'Servers\ServerTransferController@failure');
    Route::get('/transfer/success', 'Servers\ServerTransferController@success');
});


