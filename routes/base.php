<?php

Route::get('/', 'IndexController@index')->name('index')->fallback();
Route::get('/account', 'IndexController@index')->name('account');

/*
|--------------------------------------------------------------------------
| Account API Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /account/api
|
*/
Route::group(['prefix' => 'account/api'], function () {
    Route::get('/', 'ClientApiController@index')->name('account.api');
    Route::get('/new', 'ClientApiController@create')->name('account.api.new');
    Route::post('/new', 'ClientApiController@store');
    Route::delete('/revoke/{identifier}', 'ClientApiController@delete')->name('account.api.revoke');
});

/*
|--------------------------------------------------------------------------
| Account Security Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /account/security
|
*/
Route::group(['prefix' => 'account/two_factor'], function () {
    Route::get('/', 'SecurityController@index')->name('account.two_factor');
    Route::post('/totp', 'SecurityController@store')->name('account.two_factor.enable');
    Route::post('/totp/disable', 'SecurityController@delete')->name('account.two_factor.disable');
});

Route::get('/{react}', 'IndexController@index')
    ->where('react', '^(?!(\/)?(api|auth|admin|daemon)).+');
