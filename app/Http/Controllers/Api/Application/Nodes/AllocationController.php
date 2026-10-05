<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Allocations\CreatesAllocations;
use Pterodactyl\Contracts\Allocations\DeletesAllocations;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException;
use Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException;
use Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException;
use Pterodactyl\Exceptions\Service\Allocation\ServerUsingAllocationException;
use Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Allocations\DeleteAllocationRequest;
use Pterodactyl\Http\Requests\Api\Application\Allocations\GetAllocationsRequest;
use Pterodactyl\Http\Requests\Api\Application\Allocations\StoreAllocationRequest;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Transformers\Api\Application\AllocationTransformer;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Nodes', 'Create, update, retrieve, and delete Wings nodes and their allocations.')]
class AllocationController extends ApplicationApiController
{
    private const array ALLOCATION_REJECTED_ERROR = [
        'errors' => [
            [
                'code' => 'InvalidPortMappingException',
                'status' => '400',
                'detail' => 'The mapping provided for invalid was invalid and could not be processed.',
            ],
        ],
    ];

    private const array SERVER_USING_ALLOCATION_ERROR = [
        'errors' => [
            [
                'code' => 'ServerUsingAllocationException',
                'status' => '400',
                'detail' => 'A server is currently assigned to this allocation. An allocation can only be deleted if no server is currently assigned.',
            ],
        ],
    ];

    /**
     * Return all the allocations that exist for a given node.
     *
     * @return ApiPayload
     */
    #[Endpoint('List node allocations', 'Returns a paginated list of allocations belonging to a node.')]
    #[QueryParam('per_page', 'integer', 'Number of allocations to return per page, up to 100.', required: false, example: 50)]
    #[QueryParam('filter[ip]', 'string', 'Filter allocations by exact IP address.', required: false, example: '192.0.2.10')]
    #[QueryParam('filter[port]', 'integer', 'Filter allocations by exact port.', required: false, example: 25565)]
    #[QueryParam('filter[ip_alias]', 'string', 'Filter allocations by IP alias.', required: false, example: 'minecraft.example.com')]
    #[QueryParam('filter[server_id]', 'string', 'Filter allocations by assigned server ID. Empty or non-numeric values return unassigned allocations.', required: false, example: '1')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "node" and "server" when the key can read those resources.', required: false, example: 'server', enum: ['node', 'server', 'node,server'])]
    #[ResponseFromTransformer(AllocationTransformer::class, Allocation::class, collection: true, with: ['node.location'], resourceKey: 'allocation', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetAllocationsRequest $request, Node $node): array
    {
        $query = $node->allocations();

        // An empty filter[server_id] reaches us as null because of ConvertEmptyStringsToNull,
        // and Spatie skips null filters entirely, so apply the unassigned case here.
        $filters = $request->input('filter');
        if (is_array($filters) && array_key_exists('server_id', $filters) && $filters['server_id'] === null) {
            $query->whereNull('server_id');
        }

        $allocations = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('ip'),
                AllowedFilter::exact('port'),
                'ip_alias',
                AllowedFilter::callback('server_id', function (Builder $builder, $value) { // @pest-ignore-type
                    if (empty($value) || is_bool($value) || ! is_string($value) && ! is_int($value)) {
                        return $builder->whereNull('server_id');
                    }

                    // SAFETY: the boundary checks above limit the filter to an integer or string before decimal validation.
                    if (! ctype_digit((string) $value)) {
                        return $builder->whereNull('server_id');
                    }

                    return $builder->where('server_id', $value);
                }),
            ])
            ->paginate($request->perPage());

        return Fractal::collection($allocations)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toResponseArray();
    }

    /**
     * Store new allocations for a given node.
     *
     * @throws DisplayException
     * @throws CidrOutOfRangeException
     * @throws InvalidPortMappingException
     * @throws PortOutOfRangeException
     * @throws TooManyPortsInRangeException
     */
    #[Endpoint('Create node allocations', 'Assigns one or more allocation ports to a node.')]
    #[ScribeResponse(status: 204, description: 'Allocations created.')]
    #[ScribeResponse(self::ALLOCATION_REJECTED_ERROR, status: 400, description: 'The allocation IP, CIDR, or port mapping could not be processed.')]
    public function store(StoreAllocationRequest $request, CreatesAllocations $allocations, Node $node): JsonResponse
    {
        $allocations->create($node, $request->payload());

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Delete a specific allocation from the Panel.
     *
     * @throws ServerUsingAllocationException
     */
    #[Endpoint('Delete node allocation', 'Deletes an allocation from a node when no server is using it.')]
    #[ScribeResponse(status: 204, description: 'Allocation deleted.')]
    #[ScribeResponse(self::SERVER_USING_ALLOCATION_ERROR, status: 400, description: 'The allocation is currently assigned to a server.')]
    public function delete(DeleteAllocationRequest $request, DeletesAllocations $allocations, Node $node, Allocation $allocation): JsonResponse
    {
        $allocations->delete($allocation);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
