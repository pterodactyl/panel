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
    // Login specific routes
    Route::get('/login', 'LoginController@showLoginForm')->name('auth.login');
    Route::post('/login', 'LoginController@login')->middleware('recaptcha');
    Route::post('/login/checkpoint', 'LoginCheckpointController')->name('auth.login-checkpoint');

    // Forgot password route. A post to this endpoint will trigger an
    // email to be sent containing a reset token.
    Route::post('/password', 'ForgotPasswordController@sendResetLinkEmail')->name('auth.forgot-password')->middleware('recaptcha');

    // Password reset routes. This endpoint is hit after going through
    // the forgot password routes to acquire a token (or after an account
    // is created).
    Route::post('/password/reset', 'ResetPasswordController')->name('auth.reset-password')->middleware('recaptcha');
});

/*
|--------------------------------------------------------------------------
| Routes Accessable only when logged in
|--------------------------------------------------------------------------
|
| Endpoint: /auth
|
*/
Route::get('/logout', 'LoginController@logout')->name('auth.logout')->middleware('auth');
