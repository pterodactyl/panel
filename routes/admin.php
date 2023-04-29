<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Admin;

Route::get('/{react}', [Admin\BaseController::class, 'index'])->where('react', '.+');
