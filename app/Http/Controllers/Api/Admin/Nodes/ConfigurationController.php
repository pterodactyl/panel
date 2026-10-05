<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Nodes;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\GetNodeRequest;
use Pterodactyl\Models\Node;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Nodes', 'Create, update, retrieve, and delete Wings nodes.')]
class ConfigurationController extends AdminApiController
{
    private const array CONFIGURATION_EXAMPLE = [
        'debug' => false,
        'uuid' => '1b19cf3f-2f89-4f88-a81e-321e7fe326bc',
        'token_id' => 'abcd1234',
        'token' => 'secret',
        'api' => [
            'host' => '0.0.0.0',
            'port' => 8080,
            'ssl' => [
                'enabled' => true,
                'cert' => '/etc/letsencrypt/live/node.example.com/fullchain.pem',
                'key' => '/etc/letsencrypt/live/node.example.com/privkey.pem',
            ],
            'upload_limit' => 100,
        ],
        'system' => [
            'data' => '/var/lib/pterodactyl/volumes',
            'sftp' => [
                'bind_port' => 2022,
            ],
        ],
        'allowed_mounts' => ['/mnt/shared'],
        'remote' => 'https://panel.example.com',
    ];

    /**
     * Return node configuration payload.
     */
    #[Endpoint('Get node configuration', 'Returns the raw Wings configuration payload for a node, including daemon authentication tokens.')]
    #[ScribeResponse(self::CONFIGURATION_EXAMPLE, description: 'Node configuration returned.')]
    public function __invoke(GetNodeRequest $request, Node $node): JsonResponse
    {
        return new JsonResponse($node->getConfiguration());
    }
}
