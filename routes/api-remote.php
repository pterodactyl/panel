<?php

use Illuminate\Support\Facades\Route;

Route::get('/authenticate/{token}', 'ValidateKeyController@index');
Route::post('/download-file', 'FileDownloadController@index');

Route::group(['prefix' => '/scripts'], function () {
    Route::get('/{uuid}', 'EggInstallController@index')->name('api.remote.scripts');
});

// Routes for the Wings daemon.
Route::post('/sftp/auth', 'SftpAuthenticationController');
Route::group(['prefix' => '/servers/{uuid}'], function () {
    Route::get('/', 'Servers\ServerDetailsController');
});
