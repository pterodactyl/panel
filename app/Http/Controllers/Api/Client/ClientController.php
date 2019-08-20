<?php

namespace App\Http\Controllers\Api\Client;

use App\Models\User;
use App\Transformers\Api\Client\ServerTransformer;
use App\Http\Requests\Api\Client\GetServersRequest;
use App\Contracts\Repository\ServerRepositoryInterface;

class ClientController extends ClientApiController
{
    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * ClientController constructor.
     *
     * @param \App\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(ServerRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return all of the servers available to the client making the API
     * request, including servers the user has access to as a subuser.
     *
     * @param \App\Http\Requests\Api\Client\GetServersRequest $request
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

        $servers = $this->repository->filterUserAccessServers(
            $request->user(), $filter, config('pterodactyl.paginate.frontend.servers')
        );

        return $this->fractal->collection($servers)
            ->transformWith($this->getTransformer(ServerTransformer::class))
            ->toArray();
    }
}
