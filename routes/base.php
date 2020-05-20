<?php

use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;

Route::get('/', 'IndexController@index')->name('index')->fallback();
Route::get('/account', 'IndexController@index')
    ->withoutMiddleware(RequireTwoFactorAuthentication::class)
    ->name('account');

Route::get('/account/oauth/link', 'OAuthController@link')->name('account.oauth.link');
Route::get('/account/oauth/unlink', 'OAuthController@unlink')->name('account.oauth.unlink');

Route::get('/locales/{locale}/{namespace}.json', 'LocaleController')
    ->withoutMiddleware(RequireTwoFactorAuthentication::class)
    ->where('namespace', '.*');

Route::get('/{react}', 'IndexController@index')
    ->where('react', '^(?!(\/)?(api|auth|admin|daemon)).+');
