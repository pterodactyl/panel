<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Base\ExtensionFileController;
use Pterodactyl\Http\Controllers\Base\IndexController;
use Pterodactyl\Http\Controllers\Base\LocaleController;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Services\Extensions\ExtensionSettingFiles;

Route::get('/', [IndexController::class, 'index'])->name('index')->fallback();
Route::get('/account', [IndexController::class, 'index'])
    ->withoutMiddleware(RequireTwoFactorAuthentication::class)
    ->name('account');

Route::get('/locales/locale.json', LocaleController::class)
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class])
    ->where('namespace', '.*');

// Files uploaded through extension `file` settings. Public and stateless: no
// session, no cookies, so the immutable cache headers are safe behind a proxy.
Route::get('/'.ExtensionSettingFiles::ROUTE_PREFIX.'/{extension}/{file}', ExtensionFileController::class)
    ->withoutMiddleware(['web', 'auth.session', RequireTwoFactorAuthentication::class])
    ->where('extension', '[a-z][a-z0-9-]{0,47}')
    ->where('file', '[a-f0-9]{40}\.[a-z0-9]{2,5}')
    ->name('extensions.files');

// The SPA catch-all is a fallback so that routes registered after it - an extension's
// /extensions/<id> and claimed root path routes - are matched first.
Route::get('/{react}', [IndexController::class, 'index'])
    ->where('react', '^(?!(\/)?(api|auth|daemon)).+')
    ->fallback();
