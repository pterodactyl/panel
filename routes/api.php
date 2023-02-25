<?php

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('api.token')->group(function () {
    Route::patch('/users/{user}/increment', [UserController::class, 'increment']);
    Route::patch('/users/{user}/decrement', [UserController::class, 'decrement']);
    Route::patch('/users/{user}/suspend', [UserController::class, 'suspend']);
    Route::patch('/users/{user}/unsuspend', [UserController::class, 'unsuspend']);
    Route::resource('users', UserController::class)->except(['create']);

    Route::patch('/servers/{server}/suspend', [ServerController::class, 'suspend']);
    Route::patch('/servers/{server}/unsuspend', [ServerController::class, 'unSuspend']);
    Route::resource('servers', ServerController::class)->except(['store', 'create', 'edit', 'update']);

    //    Route::get('/vouchers/{voucher}/users' , [VoucherController::class , 'users']);
    Route::resource('vouchers', VoucherController::class)->except('create', 'edit');

    Route::get('/notifications/{user}', [NotificationController::class, 'index']);
    Route::get('/notifications/{user}/{notification}', [NotificationController::class, 'view']);
    Route::post('/notifications', [NotificationController::class, 'send']);
    Route::delete('/notifications/{user}/{notification}', [NotificationController::class, 'deleteOne']);
    Route::delete('/notifications/{user}', [NotificationController::class, 'delete']);
});

require __DIR__ . '/extensions_api.php';
