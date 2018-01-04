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
    Route::patch('/{user}', 'Users\UserController@update')->name('api.admin.user.update');

    Route::delete('/{user}', 'Users\UserController@delete')->name('api.admin.user.delete');
});

/*
|--------------------------------------------------------------------------
| Node Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/nodes
|
*/
Route::group(['prefix' => '/nodes'], function () {
    Route::get('/', 'Nodes\NodeController@index')->name('api.admin.node.list');
    Route::get('/{node}', 'Nodes\NodeController@view')->name('api.admin.node.view');

    Route::post('/', 'Nodes\NodeController@store')->name('api.admin.node.store');
    Route::patch('/{node}', 'Nodes\NodeController@update')->name('api.admin.node.update');

    Route::delete('/{node}', 'Nodes\NodeController@delete')->name('api.admin.node.delete');
});

/*
|--------------------------------------------------------------------------
| Location Controller Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/admin/locations
|
*/
Route::group(['prefix' => '/locations'], function () {
    Route::get('/', 'Locations\LocationController@index')->name('api.admin.location.list');
    Route::get('/{location}', 'Locations\LocationController@view')->name('api.admin.location.view');

    Route::post('/', 'Locations\LocationController@store')->name('api.admin.location.store');
    Route::patch('/{location}', 'Locations\LocationController@update')->name('api.admin.location.update');

    Route::delete('/{location}', 'Locations\LocationController@delete')->name('api.admin.location.delete');
});
