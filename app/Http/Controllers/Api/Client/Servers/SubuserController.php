<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Eloquent\SubuserRepository;
use Pterodactyl\Transformers\Api\Client\SubuserTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\GetSubuserRequest;

class SubuserController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\SubuserRepository
     */
    private $repository;

    /**
     * SubuserController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\SubuserRepository $repository
     */
    public function __construct(SubuserRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Return the users associated with this server instance.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\GetSubuserRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     */
    public function index(GetSubuserRequest $request, Server $server)
    {
        $server->subusers->load('user');

        return $this->fractal->collection($server->subusers)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }
}
