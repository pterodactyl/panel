<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
Route::get('/logout', 'LoginController@logout')->name('auth.logout')->middleware('auth');
Route::get('/login', 'LoginController@showLoginForm')->name('auth.login');
Route::get('/login/totp', 'LoginController@totp')->name('auth.totp');
Route::get('/password', 'ForgotPasswordController@showLinkRequestForm')->name('auth.password');
Route::get('/password/reset/{token}', 'ResetPasswordController@showResetForm')->name('auth.reset');

Route::post('/login', 'LoginController@login')->middleware('recaptcha');
Route::post('/login/totp', 'LoginController@totpCheckpoint');
Route::post('/password', 'ForgotPasswordController@sendResetLinkEmail')->middleware('recaptcha');
Route::post('/password/reset', 'ResetPasswordController@reset')->name('auth.reset.post')->middleware('recaptcha');
Route::post('/password/reset/{token}', 'ForgotPasswordController@sendResetLinkEmail')->middleware('recaptcha');
