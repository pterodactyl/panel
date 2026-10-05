<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Nodes;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations\GetAllocationsRequest;
use Pterodactyl\Models\Node;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Node Allocations', 'Create, update, retrieve, and delete node allocations.')]
class UniqueIpsController extends AdminApiController
{
    private const array IPS_EXAMPLE = [
        'data' => [
            '10.0.0.8',
            '10.0.0.9',
        ],
    ];

    /**
     * Return distinct allocation IPs for node.
     */
    #[Endpoint('List node allocation IPs', 'Returns distinct allocation IP addresses for a node.')]
    #[ScribeResponse(self::IPS_EXAMPLE, description: 'Allocation IPs returned.')]
    public function __invoke(GetAllocationsRequest $request, Node $node): JsonResponse
    {
        $ips = $node->allocations()
            ->select('ip')
            ->distinct()
            ->orderBy('ip')
            ->pluck('ip');

        return new JsonResponse(['data' => $ips]);
    }
}
