<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pterodactyl\Enum\ResourceLimit;
use Pterodactyl\Http\Controllers\Api\Client;
use Pterodactyl\Http\Controllers\Api\Client\AccountController;
use Pterodactyl\Http\Controllers\Api\Client\ActivityLogController;
use Pterodactyl\Http\Controllers\Api\Client\ActivityLogFilterController;
use Pterodactyl\Http\Controllers\Api\Client\ApiKeyController;
use Pterodactyl\Http\Controllers\Api\Client\ClientController;
use Pterodactyl\Http\Controllers\Api\Client\ExtensionProgressController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\BackupController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\CommandController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\DatabaseController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\FileController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\FileUploadController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\LogController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\NetworkAllocationController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\PowerController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\ResourceUtilizationController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\ScheduleController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\ScheduleTaskController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\ServerController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\SettingsController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\StartupController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\SubuserController;
use Pterodactyl\Http\Controllers\Api\Client\Servers\WebsocketController;
use Pterodactyl\Http\Controllers\Api\Client\SSHKeyController;
use Pterodactyl\Http\Controllers\Api\Client\TwoFactorController;
use Pterodactyl\Http\Middleware\Activity\AccountSubject;
use Pterodactyl\Http\Middleware\Activity\ServerSubject;
use Pterodactyl\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;
use Pterodactyl\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client
|
*/
Route::get('/', [ClientController::class, 'index'])->name('api:client.index');
Route::get('/permissions', [ClientController::class, 'permissions']);
Route::get('/extension-progress/{extension}/{job}', [ExtensionProgressController::class, 'user'])->where('extension', '[a-z][a-z0-9-]{0,47}')->whereUuid('job')->name('api:client.extension-progress');

Route::prefix('/account')->middleware(AccountSubject::class)->group(function (): void {
    Route::prefix('/')->withoutMiddleware(RequireTwoFactorAuthentication::class)->group(function (): void {
        Route::get('/', [AccountController::class, 'index'])->name('api:client.account');
        Route::get('/two-factor', [TwoFactorController::class, 'index']);
        Route::post('/two-factor', [TwoFactorController::class, 'store']);
        Route::post('/two-factor/disable', [TwoFactorController::class, 'delete']);
    });

    Route::put('/email', [AccountController::class, 'updateEmail'])
        ->middleware('throttle')
        ->name('api:client.account.update-email');
    Route::put('/password', [AccountController::class, 'updatePassword'])->name('api:client.account.update-password');

    Route::get('/activity', ActivityLogController::class)->name('api:client.account.activity');
    Route::get('/activity/filters', ActivityLogFilterController::class)->name('api:client.account.activity.filters');

    Route::get('/api-keys', [ApiKeyController::class, 'index']);
    Route::post('/api-keys', [ApiKeyController::class, 'store']);
    Route::delete('/api-keys/{identifier}', [ApiKeyController::class, 'delete']);

    Route::prefix('/ssh-keys')->group(function (): void {
        Route::get('/', [SSHKeyController::class, 'index']);
        Route::post('/', [SSHKeyController::class, 'store']);
        Route::post('/remove', [SSHKeyController::class, 'delete']);
    });
});

/*
|--------------------------------------------------------------------------
| Client Control API
|--------------------------------------------------------------------------
|
| Endpoint: /api/client/servers/{server}
|
*/
Route::group([
    'prefix' => '/servers/{server}',
    'middleware' => [
        ServerSubject::class,
        AuthenticateServerAccess::class,
        ResourceBelongsToServer::class,
    ],
], function (): void {
    Route::get('/', [ServerController::class, 'index'])->name('api:client:server.view');
    Route::middleware([ResourceLimit::Websocket->middleware()])
        ->get('/websocket', WebsocketController::class)
        ->name('api:client:server.ws');
    Route::get('/resources', ResourceUtilizationController::class)->name('api:client:server.resources');
    Route::get('/logs', LogController::class)->name('api:client:server.logs');
    Route::get('/extension-progress/{extension}/{job}', [ExtensionProgressController::class, 'server'])->where('extension', '[a-z][a-z0-9-]{0,47}')->whereUuid('job')->name('api:client:server.extension-progress');
    Route::get('/activity', Client\Servers\ActivityLogController::class)->name('api:client:server.activity');
    Route::get('/activity/filters', Client\Servers\ActivityLogFilterController::class)->name('api:client:server.activity.filters');

    Route::post('/command', [CommandController::class, 'index']);
    Route::post('/power', [PowerController::class, 'index']);

    Route::group(['prefix' => '/databases'], function (): void {
        Route::get('/', [DatabaseController::class, 'index']);
        Route::middleware([ResourceLimit::Database->middleware()])
            ->post('/', [DatabaseController::class, 'store']);
        Route::post('/{database}/rotate-password', [DatabaseController::class, 'rotatePassword']);
        Route::delete('/{database}', [DatabaseController::class, 'delete']);
    });

    Route::group(['prefix' => '/files'], function (): void {
        Route::get('/list', [FileController::class, 'directory']);
        Route::get('/contents', [FileController::class, 'contents']);
        Route::get('/download', [FileController::class, 'download']);
        Route::put('/rename', [FileController::class, 'rename']);
        Route::post('/copy', [FileController::class, 'copy']);
        Route::post('/write', [FileController::class, 'write']);
        Route::post('/compress', [FileController::class, 'compress']);
        Route::post('/decompress', [FileController::class, 'decompress']);
        Route::post('/delete', [FileController::class, 'delete']);
        Route::post('/create-folder', [FileController::class, 'create']);
        Route::post('/chmod', [FileController::class, 'chmod']);
        Route::middleware([ResourceLimit::FilePull->middleware()])
            ->post('/pull', [FileController::class, 'pull']);
        Route::get('/upload', FileUploadController::class);
    });

    Route::group(['prefix' => '/schedules'], function (): void {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::middleware([ResourceLimit::Schedule->middleware()])
            ->post('/', [ScheduleController::class, 'store']);
        Route::get('/{schedule}', [ScheduleController::class, 'view']);
        Route::post('/{schedule}', [ScheduleController::class, 'update']);
        Route::post('/{schedule}/execute', [ScheduleController::class, 'execute']);
        Route::delete('/{schedule}', [ScheduleController::class, 'delete']);

        Route::post('/{schedule}/tasks', [ScheduleTaskController::class, 'store']);
        Route::post('/{schedule}/tasks/{task}', [ScheduleTaskController::class, 'update']);
        Route::delete('/{schedule}/tasks/{task}', [ScheduleTaskController::class, 'delete']);
    });

    Route::group(['prefix' => '/network'], function (): void {
        Route::get('/allocations', [NetworkAllocationController::class, 'index']);
        Route::middleware([ResourceLimit::Allocation->middleware()])
            ->post('/allocations', [NetworkAllocationController::class, 'store']);
        Route::post('/allocations/{allocation}', [NetworkAllocationController::class, 'update']);
        Route::post('/allocations/{allocation}/primary', [NetworkAllocationController::class, 'setPrimary']);
        Route::delete('/allocations/{allocation}', [NetworkAllocationController::class, 'delete']);
    });

    Route::group(['prefix' => '/users'], function (): void {
        Route::get('/', [SubuserController::class, 'index']);
        Route::middleware([ResourceLimit::Subuser->middleware()])
            ->post('/', [SubuserController::class, 'store']);
        Route::get('/{user}', [SubuserController::class, 'view']);
        Route::post('/{user}', [SubuserController::class, 'update']);
        Route::delete('/{user}', [SubuserController::class, 'delete']);
    });

    Route::group(['prefix' => '/backups'], function (): void {
        Route::get('/', [BackupController::class, 'index']);
        Route::post('/', [BackupController::class, 'store']);
        Route::get('/{backup}', [BackupController::class, 'view']);
        Route::get('/{backup}/download', [BackupController::class, 'download']);
        Route::post('/{backup}/lock', [BackupController::class, 'toggleLock']);
        Route::middleware([ResourceLimit::Backup->middleware()])
            ->post('/{backup}/restore', [BackupController::class, 'restore']);
        Route::delete('/{backup}', [BackupController::class, 'delete']);
    });

    Route::group(['prefix' => '/startup'], function (): void {
        Route::get('/', [StartupController::class, 'index']);
        Route::put('/variable', [StartupController::class, 'update']);
    });

    Route::group(['prefix' => '/settings'], function (): void {
        Route::post('/rename', [SettingsController::class, 'rename']);
        Route::post('/reinstall', [SettingsController::class, 'reinstall']);
        Route::put('/docker-image', [SettingsController::class, 'dockerImage']);
    });
});
