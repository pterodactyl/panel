<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin;

Route::get('/', [Admin\BaseController::class, 'index'])->name('admin.index')->fallback();
Route::get('/{react}', [Admin\BaseController::class, 'index'])->where('react', '.+');
