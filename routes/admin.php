<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Endpoint: /admin
|
*/
Route::get('/', 'BaseController@index')->name('admin.index')->fallback();
Route::get('/{react}', 'BaseController@index')
    ->where('react', '.+');
