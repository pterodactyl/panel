<?php

use Illuminate\Support\Facades\Route;

Route::get('/authenticate/{token}', 'ValidateKeyController@index');
Route::post('/websocket/{token}', 'ValidateWebsocketController');
Route::post('/download-file', 'FileDownloadController@index');

Route::group(['prefix' => '/scripts'], function () {
    Route::get('/{uuid}', 'EggInstallController@index')->name('api.remote.scripts');
});

Route::group(['prefix' => '/sftp'], function () {
    Route::post('/', 'SftpController@index')->name('api.remote.sftp');
});

Route::group(['prefix' => '/servers/{uuid}'], function () {
    Route::get('/configuration', 'Servers\ServerConfigurationController');
});
