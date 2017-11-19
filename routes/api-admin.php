<?php

/*
|--------------------------------------------------------------------------
| User Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/users
|
*/
Route::group(['prefix' => '/users'], function () {
    Route::get('/', 'Users\UserController@index')->name('api.admin.user.list');
    Route::get('/{id}', 'Users\UserController@view');

    Route::post('/', 'Users\UserController@store');
    Route::put('/{id}', 'Users\UserController@update');

    Route::delete('/{id}', 'Users\UserController@delete');
});
