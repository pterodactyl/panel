<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Api\Remote\ActivityProcessingController;
use Pterodactyl\Http\Controllers\Api\Remote\Backups\BackupRemoteUploadController;
use Pterodactyl\Http\Controllers\Api\Remote\Backups\BackupStatusController;
use Pterodactyl\Http\Controllers\Api\Remote\Servers\ServerDetailsController;
use Pterodactyl\Http\Controllers\Api\Remote\Servers\ServerInstallController;
use Pterodactyl\Http\Controllers\Api\Remote\Servers\ServerTransferController;
use Pterodactyl\Http\Controllers\Api\Remote\SftpAuthenticationController;

// Routes for the Wings daemon.
Route::post('/sftp/auth', SftpAuthenticationController::class);

Route::get('/servers', [ServerDetailsController::class, 'list']);
Route::post('/servers/reset', [ServerDetailsController::class, 'resetState']);
Route::post('/activity', ActivityProcessingController::class);

Route::group(['prefix' => '/servers/{uuid}'], function (): void {
    Route::get('/', ServerDetailsController::class);
    Route::get('/install', [ServerInstallController::class, 'index']);
    Route::post('/install', [ServerInstallController::class, 'store']);
    Route::post('/transfer/failure', [ServerTransferController::class, 'failure']);
    Route::post('/transfer/success', [ServerTransferController::class, 'success']);
});

Route::group(['prefix' => '/backups'], function (): void {
    Route::get('/{backup}', BackupRemoteUploadController::class);
    Route::post('/{backup}', [BackupStatusController::class, 'index']);
    Route::post('/{backup}/restore', [BackupStatusController::class, 'restore']);
});
