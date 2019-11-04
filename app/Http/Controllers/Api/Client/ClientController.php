<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use Pterodactyl\Models\Permission;
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
        // Check for the filter parameter on the request.
        switch ($request->input('filter')) {
            case 'all':
                $filter = User::FILTER_LEVEL_ALL;
                break;
            case 'admin':
                $filter = User::FILTER_LEVEL_ADMIN;
                break;
            case 'owner':
                $filter = User::FILTER_LEVEL_OWNER;
                break;
            case 'subuser-of':
            default:
                $filter = User::FILTER_LEVEL_SUBUSER;
                break;
        }

        $servers = $this->repository
            ->setSearchTerm($request->input('query'))
            ->filterUserAccessServers(
                $request->user(), $filter, config('pterodactyl.paginate.frontend.servers')
            );

        return $this->fractal->collection($servers)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }

    /**
     * Returns all of the subuser permissions available on the system.
     *
     * @return array
     */
    public function permissions()
    {
        $permissions = Permission::permissions()->map(function ($values, $key) {
            return Collection::make($values)->map(function ($permission) use ($key) {
                return $key . '.' . $permission;
            })->values()->toArray();
        })->flatten();

        return [
            'object' => 'system_permissions',
            'attributes' => [
                'permissions' => $permissions,
            ],
        ];
    }
}
