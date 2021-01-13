<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Server Routes
|--------------------------------------------------------------------------
|
| Endpoint: /server
|
*/
Route::get('/')->name('server.index');
Route::get('/console')->name('server.console');
