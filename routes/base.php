<?php

Route::get('/', 'IndexController@index')->name('index')->fallback();
Route::get('/account', 'IndexController@index')->name('account');

Route::get('/locales/{locale}/{namespace}.json', 'LocaleController')
    ->where('namespace', '.*');

Route::get('/{react}', 'IndexController@index')
    ->where('react', '^(?!(\/)?(api|auth|admin|daemon)).+');
