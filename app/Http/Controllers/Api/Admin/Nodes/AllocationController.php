<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Nodes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Pterodactyl\Contracts\Allocations\CreatesAllocations;
use Pterodactyl\Contracts\Allocations\DeletesAllocations;
use Pterodactyl\Contracts\Allocations\UpdatesAllocations;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations\DeleteAllocationRequest;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations\GetAllocationsRequest;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations\StoreAllocationRequest;
use Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations\UpdateAllocationRequest;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Node;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Transformers\Api\Admin\AllocationTransformer;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Node Allocations', 'Create, update, retrieve, and delete node allocations.')]
class AllocationController extends AdminApiController
{
    public const array SERVER_USING_ALLOCATION_ERROR = [
        'errors' => [
            [
                'code' => 'ServerUsingAllocationException',
                'status' => '400',
                'detail' => 'This allocation is currently assigned to a server.',
            ],
        ],
    ];

    /**
     * List allocations.
     *
     * @return ApiPayload
     */
    #[Endpoint('List node allocations', 'Returns a paginated list of allocations for a node.')]
    #[QueryParam('filter[ip]', 'string', 'Filter allocations by exact IP address.', required: false, example: '192.168.1.1')]
    #[QueryParam('filter[port]', 'integer', 'Filter allocations by exact port.', required: false, example: 25565)]
    #[QueryParam('filter[ip_alias]', 'string', 'Filter allocations by IP alias.', required: false, example: 'game.example.com')]
    #[QueryParam('filter[server_id]', 'string', 'Filter allocations by server ID, or pass an empty value to return unassigned allocations.', required: false, example: '42')]
    #[QueryParam('sort', 'string', 'Sort allocations by id, ip, or port. Prefix with a hyphen for descending order.', required: false, example: 'port')]
    #[QueryParam('page', 'integer', 'Page number to return.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[ResponseFromTransformer(AllocationTransformer::class, Allocation::class, description: 'Allocations returned.', collection: true, resourceKey: 'allocation', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetAllocationsRequest $request, Node $node): array
    {
        // Eager-load the owning server so the transformer avoids an N+1 on server name.
        $query = $node->allocations()->with('server');

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
            ->allowedSorts(['id', 'ip', 'port'])
            ->paginate($request->perPage());

        return Fractal::collection($allocations)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create allocation.
     */
    #[Endpoint('Create node allocations', 'Creates one or more allocations on a node from IP addresses and ports or port ranges.')]
    #[ScribeResponse(status: 204, description: 'Allocations created.')]
    public function store(StoreAllocationRequest $request, CreatesAllocations $allocations, Node $node): Response
    {
        $ips = JsonValueGuard::stringList($request->validated('ip'));
        $ports = JsonValueGuard::integerStringList($request->validated('ports'));
        $alias = JsonValueGuard::nullableString($request->validated('alias'));

        // The creation action handles one (CIDR-capable) IP at a time, so fan out per IP.
        foreach ($ips as $ip) {
            $allocations->create($node, [
                'allocation_ip' => $ip,
                'allocation_ports' => $ports,
                'allocation_alias' => $alias,
            ]);
        }

        Activity::event('admin:node-allocation.create')
            ->subject($node)
            ->property('address', $ips)
            ->property('ports', $ports)
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Update allocation.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update node allocation', 'Updates the alias for a single allocation on a node.')]
    #[ResponseFromTransformer(AllocationTransformer::class, Allocation::class, description: 'Allocation updated.', resourceKey: 'allocation')]
    public function update(UpdateAllocationRequest $request, UpdatesAllocations $allocations, Node $node, Allocation $allocation): array
    {
        $this->assertAllocationBelongsToNode($node, $allocation);

        $allocation = $allocations->update($allocation, JsonValueGuard::nullableString($request->validated('alias')));

        Activity::event('admin:node-allocation.alias')
            ->subject($allocation)
            ->property('alias', $allocation->ip_alias)
            ->log();

        return Fractal::item($allocation)
            ->transformWith($this->getTransformer(AllocationTransformer::class))
            ->toResponseArray();
    }

    /**
     * Delete allocation.
     */
    #[Endpoint('Delete node allocation', 'Deletes a single unassigned allocation from a node.')]
    #[ScribeResponse(status: 204, description: 'Allocation deleted.')]
    #[ScribeResponse(self::SERVER_USING_ALLOCATION_ERROR, status: 400, description: 'The allocation is currently assigned to a server.')]
    public function destroy(DeleteAllocationRequest $request, DeletesAllocations $allocations, Node $node, Allocation $allocation): Response
    {
        $this->assertAllocationBelongsToNode($node, $allocation);

        $allocations->delete($allocation);

        Activity::event('admin:node-allocation.delete')
            ->subject($allocation)
            ->property('address', $allocation->ip)
            ->property('port', $allocation->port)
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Ensure allocation belongs to node.
     */
    private function assertAllocationBelongsToNode(Node $node, Allocation $allocation): void
    {
        if ($allocation->node_id !== $node->id) {
            throw (new ModelNotFoundException)->setModel(Allocation::class);
        }
    }
}
