<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Nodes\GetNodeConfigurationRequest;
use Pterodactyl\Models\Node;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Nodes', 'Create, update, retrieve, and delete Wings nodes and their allocations.')]
class NodeConfigurationController extends ApplicationApiController
{
    private const array CONFIGURATION_EXAMPLE = [
        'debug' => false,
        'uuid' => '3d8dd7f9-07a1-4d65-8db0-8c8921f2f4f7',
        'token_id' => 'abcdefghijklmnop',
        'token' => 'example-node-token',
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
        'remote' => 'https://panel.example.test',
    ];

    /**
     * Returns the configuration information for a node. This allows for automated deployments
     * to remote machines so long as an API key is provided to the machine to make the request
     * with, and the node is known.
     */
    #[Endpoint('Get node configuration', 'Returns the Wings configuration payload for a node, including node authentication tokens. Requires write access to nodes.')]
    #[ScribeResponse(self::CONFIGURATION_EXAMPLE, status: 200, description: 'Node configuration.')]
    public function __invoke(GetNodeConfigurationRequest $request, Node $node): JsonResponse
    {
        return new JsonResponse($node->getConfiguration());
    }
}
