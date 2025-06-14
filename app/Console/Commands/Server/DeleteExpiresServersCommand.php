<?php

namespace Pterodactyl\Console\Commands\Server;

use Carbon\Carbon;
use Pterodactyl\Models\Server;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class DeleteExpiresServersCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'p:server:expired:delete';

    /**
     * @var string
     */
    protected $description = 'Delete expired servers.';

    /**
     * @param SettingsRepositoryInterface $settingsRepository
     * @param ServerDeletionService $serverDeletionService
     * @param SuspensionService $suspensionService
     */
    public function handle(SettingsRepositoryInterface $settingsRepository, ServerDeletionService $serverDeletionService, SuspensionService $suspensionService)
    {
        $servers = DB::table('servers')->whereNotNull('expired_at')->get();

        foreach ($servers as $server) {
            if (Carbon::now() > Carbon::parse($server->expired_at)) {
                if ($settingsRepository->get('settings::shop::servers::days', 0) == 0) {
                    try {
                        $serverDeletionService->handle(Server::find($server->id));
                    } catch (DisplayException | \Throwable $e) {
                        continue;
                    }
                } else {
                    if (Carbon::now() > Carbon::parse($server->expired_at)->addDays($settingsRepository->get('settings::shop::servers::days', 0))) {
                        try {
                            $serverDeletionService->handle(Server::find($server->id));
                        } catch (DisplayException | \Throwable $e) {
                            continue;
                        }
                    } else if ($server->status != 'suspended') {
                        try {
                            $suspensionService->toggle(Server::find($server->id), SuspensionService::ACTION_SUSPEND);
                        } catch (\Throwable $e) {
                            continue;
                        }
                    }
                }
            }
        }
    }
}
