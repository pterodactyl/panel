<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Nodes;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\GetUtilizationRequest;
use Pterodactyl\Models\Node;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Nodes', 'Create, update, retrieve, and delete Wings nodes.')]
class UtilizationController extends AdminApiController
{
    private const array UTILIZATION_EXAMPLE = [
        'disk' => [
            'value' => '1,024',
            'max' => '102,400',
            'percent' => 1.0,
            'css' => 'green',
        ],
        'memory' => [
            'value' => '512',
            'max' => '32,768',
            'percent' => 1.5625,
            'css' => 'green',
        ],
    ];

    /**
     * Return node resource utilization.
     */
    #[Endpoint('Get node utilization', 'Returns aggregate resource utilization for a node.')]
    #[ScribeResponse(self::UTILIZATION_EXAMPLE, description: 'Utilization returned.')]
    public function __invoke(GetUtilizationRequest $request, Node $node): JsonResponse
    {
        return new JsonResponse($node->utilization());
    }
}
