<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Models\User;
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
        $level = $request->getFilterLevel();
        $transformer = $this->getTransformer(ServerTransformer::class);

        // Start the query builder and ensure we eager load any requested relationships from the request.
        $builder = Server::query()->with($this->getIncludesForTransformer($transformer, ['node']));

        if ($level === User::FILTER_LEVEL_OWNER) {
            $builder = $builder->where('owner_id', $request->user()->id);
        }
        // If set to all, display all servers they can access, including those they access as an
        // admin. If set to subuser, only return the servers they can access because they are owner,
        // or marked as a subuser of the server.
        elseif (($level === User::FILTER_LEVEL_ALL && ! $user->root_admin) || $level === User::FILTER_LEVEL_SUBUSER) {
            $builder = $builder->whereIn('id', $user->accessibleServers()->pluck('id')->all());
        }
        // If set to admin, only display the servers a user can access because they are an administrator.
        // This means only servers the user would not have access to if they were not an admin (because they
        // are not an owner or subuser) are returned.
        elseif ($level === User::FILTER_LEVEL_ADMIN && $user->root_admin) {
            $builder = $builder->whereNotIn('id', $user->accessibleServers()->pluck('id')->all());
        }

        $builder = QueryBuilder::for($builder)->allowedFilters(
            'uuid', 'name', 'external_id'
        );

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
