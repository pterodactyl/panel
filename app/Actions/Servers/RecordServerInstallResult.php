<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Contracts\Servers\RecordsServerInstallResults;
use Pterodactyl\Events\Server\Installed as ServerInstalled;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\ServerInstallService;

final readonly class RecordServerInstallResult implements RecordsServerInstallResults
{
    public function __construct(private ServerInstallService $serverInstallService) {}

    public function record(Request $request, string $uuid, bool $successful, bool $reinstall): Server
    {
        $server = $this->serverInstallService->resolveForNode($request, $uuid);
        $status = null;
        if (! $successful) {
            $status = $reinstall ? Server::STATUS_REINSTALL_FAILED : Server::STATUS_INSTALL_FAILED;
        }

        if ($server->status === Server::STATUS_SUSPENDED) {
            $status = Server::STATUS_SUSPENDED;
        }

        $isInitialInstall = $server->installed_at === null;
        $server->forceFill(['status' => $status, 'installed_at' => CarbonImmutable::now()])->saveOrFail();
        if ($isInitialInstall && config()->get('pterodactyl.email.send_install_notification', true)) {
            Event::dispatch(new ServerInstalled($server));
        } elseif (! $isInitialInstall && config()->get('pterodactyl.email.send_reinstall_notification', true)) {
            Event::dispatch(new ServerInstalled($server));
        }

        Event::dispatch(new OperationCompleted($server->uuid, $reinstall ? 'reinstall' : 'install', $successful, $server->uuid));

        return $server;
    }
}
