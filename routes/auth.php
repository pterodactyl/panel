<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Auth\ForgotPasswordController;
use Pterodactyl\Http\Controllers\Auth\LoginCheckpointController;
use Pterodactyl\Http\Controllers\Auth\LoginController;
use Pterodactyl\Http\Controllers\Auth\ResetPasswordController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Endpoint: /auth
|
*/

// These routes are defined so that we can continue to reference them programmatically.
// They all route to the same controller function which passes off to React.
Route::get('/login', [LoginController::class, 'index'])->name('auth.login');
Route::get('/password', [LoginController::class, 'index'])->name('auth.forgot-password');
Route::get('/password/reset/{token}', [LoginController::class, 'index'])->name('auth.reset');

// Apply a throttle to authentication action endpoints, in addition to the
// recaptcha endpoints to slow down manual attack spammers even more. 🤷‍
//
// @see bootstrap/app.php (withRouting)
Route::middleware(['throttle:authentication'])->group(function (): void {
    // Login endpoints.
    Route::post('/login', [LoginController::class, 'login'])->middleware('recaptcha');
    Route::post('/login/checkpoint', LoginCheckpointController::class)->name('auth.login-checkpoint');

    // Forgot password route. A post to this endpoint will trigger an
    // email to be sent containing a reset token.
    Route::post('/password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('auth.post.forgot-password')
        ->middleware('recaptcha');

    // Password reset routes. This endpoint is hit after going through
    // the forgot password routes to acquire a token (or after an account
    // is created).
    Route::post('/password/reset', ResetPasswordController::class)
        ->name('auth.reset-password')
        ->middleware('recaptcha');
});

// Remove the guest middleware and apply the authenticated middleware to this endpoint,
// so it cannot be used unless you're already logged in.
Route::post('/logout', [LoginController::class, 'logout'])
    ->withoutMiddleware('guest')
    ->middleware('auth')
    ->name('auth.logout');

// Catch any other combinations of routes and pass them off to the React component.
Route::fallback([LoginController::class, 'index']);
