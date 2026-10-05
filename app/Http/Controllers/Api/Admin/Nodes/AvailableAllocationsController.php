<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Nodes;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations\GetAllocationsRequest;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Transformers\Api\Admin\AllocationTransformer;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Node Allocations', 'Create, update, retrieve, and delete node allocations.')]
class AvailableAllocationsController extends AdminApiController
{
    /**
     * Return available allocations for node.
     *
     * @return ApiPayload
     */
    #[Endpoint('List available node allocations', 'Returns unassigned allocations for a node.')]
    #[QueryParam('filter[ip]', 'string', 'Filter allocations by exact IP address.', example: '192.168.1.1')]
    #[QueryParam('filter[port]', 'integer', 'Filter allocations by exact port.', example: 25565)]
    #[QueryParam('filter[ip_alias]', 'string', 'Filter allocations by IP alias.', example: 'game.example.com')]
    #[QueryParam('sort', 'string', 'Sort allocations by id, ip, or port. Prefix with a hyphen for descending order.', example: 'port')]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', example: 50)]
    #[ResponseFromTransformer(AllocationTransformer::class, Allocation::class, description: 'Available allocations returned.', collection: true, resourceKey: 'allocation', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function __invoke(GetAllocationsRequest $request, Node $node): array
    {
        $allocations = QueryBuilder::for($node->allocations()->whereNull('server_id'))
            ->allowedFilters([
                AllowedFilter::exact('ip'),
                AllowedFilter::exact('port'),
                'ip_alias',
            ])
            ->allowedSorts(['id', 'ip', 'port'])
            ->paginate($request->perPage());

        return Fractal::collection($allocations)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toResponseArray();
    }
}
