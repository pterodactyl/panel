<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Spatie\QueryBuilder\QueryBuilder;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Transformers\Api\Client\ServerTransformer;
use Pterodactyl\Http\Requests\Api\Client\GetServersRequest;

class ClientController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * ClientController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\ServerRepository $repository
     */
    public function __construct(ServerRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return all of the servers available to the client making the API
     * request, including servers the user has access to as a subuser.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\GetServersRequest $request
     * @return array
     */
    public function index(GetServersRequest $request): array
    {
        $user = $request->user();
        $transformer = $this->getTransformer(ServerTransformer::class);

        // Start the query builder and ensure we eager load any requested relationships from the request.
        $builder = QueryBuilder::for(
            Server::query()->with($this->getIncludesForTransformer($transformer, ['node']))
        )->allowedFilters('uuid', 'name', 'external_id');

        // Either return all of the servers the user has access to because they are an admin `?type=admin` or
        // just return all of the servers the user has access to because they are the owner or a subuser of the
        // server.
        if ($request->input('type') === 'admin') {
            $builder = $user->root_admin
                ? $builder->whereNotIn('id', $user->accessibleServers()->pluck('id')->all())
                // If they aren't an admin but want all the admin servers don't fail the request, just
                // make it a query that will never return any results back.
                : $builder->whereRaw('1 = 2');
        } elseif ($request->input('type') === 'owner') {
            $builder = $builder->where('owner_id', $user->id);
        } else {
            $builder = $builder->whereIn('id', $user->accessibleServers()->pluck('id')->all());
        }

        $servers = $builder->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return $this->fractal->transformWith($transformer)->collection($servers)->toArray();
    }

    /**
     * Returns all of the subuser permissions available on the system.
     *
     * @return array
     */
    public function permissions()
    {
        return [
            'object' => 'system_permissions',
            'attributes' => [
                'permissions' => Permission::permissions(),
            ],
        ];
    }
}
