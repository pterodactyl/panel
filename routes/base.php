<?php

use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;

Route::get('/', 'IndexController@index')->name('index')->fallback();
Route::get('/account', 'IndexController@index')
    ->withoutMiddleware(RequireTwoFactorAuthentication::class)
    ->name('account');

Route::get('/locales/{locale}/{namespace}.json', 'LocaleController')
    ->withoutMiddleware(RequireTwoFactorAuthentication::class)
    ->where('namespace', '.*');

Route::get('/{react}', 'IndexController@index')
    ->where('react', '^(?!(\/)?(api|auth|admin|daemon)).+');
