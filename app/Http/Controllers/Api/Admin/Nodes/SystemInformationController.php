<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Nodes;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\GetSystemInformationRequest;
use Pterodactyl\Models\Node;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Nodes', 'Create, update, retrieve, and delete Wings nodes.')]
class SystemInformationController extends AdminApiController
{
    private const array SYSTEM_INFORMATION_EXAMPLE = [
        'architecture' => 'amd64',
        'cpu_count' => 8,
        'kernel_version' => '6.8.0',
        'os' => 'linux',
        'version' => '1.11.0',
    ];

    private const array DAEMON_ERROR = [
        'errors' => [
            [
                'code' => 'DaemonConnectionException',
                'detail' => 'Could not connect to the daemon.',
            ],
        ],
    ];

    /**
     * Return node system information.
     */
    #[Endpoint('Get node system information', 'Proxies Wings system information for a node.')]
    #[ScribeResponse(self::SYSTEM_INFORMATION_EXAMPLE, description: 'System information returned.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'The panel could not connect to Wings.')]
    public function __invoke(GetSystemInformationRequest $request, Node $node): JsonResponse
    {
        return new JsonResponse(Daemon::node($node)->systemInformation());
    }
}
