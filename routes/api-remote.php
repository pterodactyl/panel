<?php

Route::get('/authenticate/{token}', 'ValidateKeyController@index')->name('api.remote.authenticate');
Route::post('/download-file', 'FileDownloadController@index')->name('api.remote.download_file');

Route::group(['prefix' => '/eggs'], function () {
    Route::get('/', 'EggRetrievalController@index')->name('api.remote.eggs');
    Route::get('/{uuid}', 'EggRetrievalController@download')->name('api.remote.eggs.download');
});

Route::group(['prefix' => '/scripts'], function () {
    Route::get('/{uuid}', 'EggInstallController@index')->name('api.remote.scripts');
});

Route::group(['prefix' => '/sftp'], function () {
    Route::post('/', 'SftpController@index')->name('api.remote.sftp');
});
