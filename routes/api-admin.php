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
    Route::get('/{user}', 'Users\UserController@view')->name('api.admin.user.view');

    Route::post('/', 'Users\UserController@store')->name('api.admin.user.store');
    Route::put('/{user}', 'Users\UserController@update')->name('api.admin.user.update');

    Route::delete('/{user}', 'Users\UserController@delete')->name('api.admin.user.delete');
});
