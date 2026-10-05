<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\ToggleInstallRequest;
use Pterodactyl\Models\Server;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Servers', 'Create, update, retrieve, and operate servers.')]
class ToggleInstallController extends AdminApiController
{
    private const array INSTALL_FAILED_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'This server is marked as failed and cannot have its install state toggled.',
            ],
        ],
    ];

    /**
     * Toggle server install state.
     */
    #[Endpoint('Toggle server install state', 'Toggles a server between installed and installing states unless it is marked as failed.')]
    #[ScribeResponse(status: 204, description: 'Server install state toggled.')]
    #[ScribeResponse(self::INSTALL_FAILED_ERROR, status: 400, description: 'The server is marked as failed.')]
    public function __invoke(ToggleInstallRequest $request, Server $server): Response
    {
        if ($server->status === Server::STATUS_INSTALL_FAILED) {
            throw new DisplayException(trans('admin/server.exceptions.marked_as_failed'));
        }

        $server->forceFill([
            'status' => $server->isInstalled() ? Server::STATUS_INSTALLING : null,
        ])->saveOrFail();

        Activity::event('admin:server.toggle-install')
            ->subject($server)
            ->property('name', $server->name)
            ->property('status', $server->status)
            ->log();

        return $this->returnNoContent();
    }
}
