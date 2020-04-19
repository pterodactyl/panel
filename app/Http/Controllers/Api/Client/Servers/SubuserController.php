<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Permission;
use Pterodactyl\Repositories\Eloquent\SubuserRepository;
use Pterodactyl\Services\Subusers\SubuserCreationService;
use Pterodactyl\Transformers\Api\Client\SubuserTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\GetSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\StoreSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\DeleteSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\UpdateSubuserRequest;

class SubuserController extends ClientApiController
{
    /**
     * @var \Pterodactyl\Repositories\Eloquent\SubuserRepository
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserCreationService
     */
    private $creationService;

    /**
     * SubuserController constructor.
     *
     * @param \Pterodactyl\Repositories\Eloquent\SubuserRepository $repository
     * @param \Pterodactyl\Services\Subusers\SubuserCreationService $creationService
     */
    public function __construct(
        SubuserRepository $repository,
        SubuserCreationService $creationService
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->creationService = $creationService;
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
        return $this->fractal->collection($server->subusers)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    /**
     * Create a new subuser for the given server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\StoreSubuserRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException
     * @throws \Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException
     * @throws \Throwable
     */
    public function store(StoreSubuserRequest $request, Server $server)
    {
        $response = $this->creationService->handle(
            $server, $request->input('email'), $this->getDefaultPermissions($request)
        );

        return $this->fractal->item($response)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    /**
     * Update a given subuser in the system for the server.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\UpdateSubuserRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateSubuserRequest $request, Server $server): array
    {
        $subuser = $request->endpointSubuser();
        $this->repository->update($subuser->id, [
            'permissions' => $this->getDefaultPermissions($request),
        ]);

        return $this->fractal->item($subuser->refresh())
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    /**
     * Removes a subusers from a server's assignment.
     *
     * @param \Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\DeleteSubuserRequest $request
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteSubuserRequest $request, Server $server)
    {
        $this->repository->delete($request->endpointSubuser()->id);

        return JsonResponse::create([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Returns the default permissions for all subusers to ensure none are ever removed wrongly.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function getDefaultPermissions(Request $request): array
    {
        return array_unique(array_merge($request->input('permissions') ?? [], [Permission::ACTION_WEBSOCKET_CONNECT]));
    }
}
