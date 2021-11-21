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
    // These routes are defined so that we can continue to reference them programatically.
    // They all route to the same controller function which passes off to Vuejs.
    Route::get('/login', 'LoginController@index')->name('auth.login');
    Route::get('/password', 'LoginController@index')->name('auth.forgot-password');
    Route::get('/password/reset/{token}', 'LoginController@index')->name('auth.reset');

    // Apply a throttle to authentication action endpoints, in addition to the
    // recaptcha endpoints to slow down manual attack spammers even more. 🤷‍
    //
    // @see \Pterodactyl\Providers\RouteServiceProvider
    Route::middleware(['throttle:authentication'])->group(function () {
        // Login endpoints.
        Route::post('/login', 'LoginController@login')->middleware('recaptcha');
        Route::post('/login/checkpoint', 'LoginCheckpointController')->name('auth.login-checkpoint');

        // Forgot password route. A post to this endpoint will trigger an
        // email to be sent containing a reset token.
        Route::post('/password', 'ForgotPasswordController@sendResetLinkEmail')
            ->name('auth.post.forgot-password')
            ->middleware('recaptcha');
    });

    // Password reset routes. This endpoint is hit after going through
    // the forgot password routes to acquire a token (or after an account
    // is created).
    Route::post('/password/reset', 'ResetPasswordController')->name('auth.reset-password');

    // Catch any other combinations of routes and pass them off to the Vuejs component.
    Route::fallback('LoginController@index');
});

/*
|--------------------------------------------------------------------------
| Routes Accessible only when logged in
|--------------------------------------------------------------------------
|
| Endpoint: /auth
|
*/
Route::post('/logout', 'LoginController@logout')->name('auth.logout')->middleware('auth', 'csrf');
