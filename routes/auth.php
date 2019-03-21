<?php

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Endpoint: /auth
|
*/
Route::group(['middleware' => 'guest'], function () {
    Route::get('/login', 'LoginController@showLoginForm')->name('auth.login');
    Route::get('/login/totp', 'LoginController@totp')->name('auth.totp');
    Route::get('/login/oauth2/{driver?}', 'OAuth2Controller@redirect')->name('auth.oauth2');
    Route::get('/password', 'ForgotPasswordController@showLinkRequestForm')->name('auth.password');
    Route::get('/password/reset/{token}', 'ResetPasswordController@showResetForm')->name('auth.reset');

    Route::post('/login', 'LoginController@login')->middleware('recaptcha');
    Route::post('/login/totp', 'LoginController@loginUsingTotp');
    Route::post('/password', 'ForgotPasswordController@sendResetLinkEmail')->middleware('recaptcha');
    Route::post('/password/reset', 'ResetPasswordController@reset')->name('auth.reset.post')->middleware('recaptcha');
    Route::post('/password/reset/{token}', 'ForgotPasswordController@sendResetLinkEmail')->middleware('recaptcha');
});

Route::get('/oauth2/callback', 'OAuth2Controller@login')->name('oauth2.callback');

/*
|--------------------------------------------------------------------------
| Routes Accessible only when logged in
|--------------------------------------------------------------------------
|
| Endpoint: /auth
|
*/
Route::get('/logout', 'LoginController@logout')->name('auth.logout')->middleware('auth');
