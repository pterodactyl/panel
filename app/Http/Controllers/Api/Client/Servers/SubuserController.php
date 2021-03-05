<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Permission;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Repositories\Eloquent\SubuserRepository;
use Pterodactyl\Services\Subusers\SubuserCreationService;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Transformers\Api\Client\SubuserTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\GetSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\StoreSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\DeleteSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\UpdateSubuserRequest;

class SubuserController extends ClientApiController
{
    private SubuserRepository $repository;
    private SubuserCreationService $creationService;
    private DaemonServerRepository $serverRepository;

    /**
     * SubuserController constructor.
     */
    public function __construct(
        SubuserRepository $repository,
        SubuserCreationService $creationService,
        DaemonServerRepository $serverRepository
    ) {
        parent::__construct();

        $this->repository = $repository;
        $this->creationService = $creationService;
        $this->serverRepository = $serverRepository;
    }

    /**
     * Return the users associated with this server instance.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function index(GetSubuserRequest $request, Server $server): array
    {
        return $this->fractal->collection($server->subusers)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    /**
     * Returns a single subuser associated with this server instance.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function view(GetSubuserRequest $request): array
    {
        $subuser = $request->attributes->get('subuser');

        return $this->fractal->item($subuser)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    /**
     * Create a new subuser for the given server.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException
     * @throws \Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException
     * @throws \Throwable
     */
    public function store(StoreSubuserRequest $request, Server $server): array
    {
        $response = $this->creationService->handle(
            $server,
            $request->input('email'),
            $this->getDefaultPermissions($request)
        );

        return $this->fractal->item($response)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    /**
     * Update a given subuser in the system for the server.
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function update(UpdateSubuserRequest $request, Server $server): array
    {
        /** @var \Pterodactyl\Models\Subuser $subuser */
        $subuser = $request->attributes->get('subuser');

        $permissions = $this->getDefaultPermissions($request);
        $current = $subuser->permissions;

        sort($permissions);
        sort($current);

        // Only update the database and hit up the Wings instance to invalidate JTI's if the permissions
        // have actually changed for the user.
        if ($permissions !== $current) {
            $this->repository->update($subuser->id, [
                'permissions' => $this->getDefaultPermissions($request),
            ]);

            try {
                $this->serverRepository->setServer($server)->revokeUserJTI($subuser->user_id);
            } catch (DaemonConnectionException $exception) {
                // Don't block this request if we can't connect to the Wings instance. Chances are it is
                // offline in this event and the token will be invalid anyways once Wings boots back.
                Log::warning($exception, ['user_id' => $subuser->user_id, 'server_id' => $server->id]);
            }
        }

        return $this->fractal->item($subuser->refresh())
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toArray();
    }

    /**
     * Removes a subusers from a server's assignment.
     */
    public function delete(DeleteSubuserRequest $request, Server $server): Response
    {
        /** @var \Pterodactyl\Models\Subuser $subuser */
        $subuser = $request->attributes->get('subuser');

        $this->repository->delete($subuser->id);

        try {
            $this->serverRepository->setServer($server)->revokeUserJTI($subuser->user_id);
        } catch (DaemonConnectionException $exception) {
            // Don't block this request if we can't connect to the Wings instance.
            Log::warning($exception, ['user_id' => $subuser->user_id, 'server_id' => $server->id]);
        }

        return $this->returnNoContent();
    }

    /**
     * Returns the default permissions for all subusers to ensure none are ever removed wrongly.
     */
    protected function getDefaultPermissions(Request $request): array
    {
        return array_unique(array_merge($request->input('permissions') ?? [], [Permission::ACTION_WEBSOCKET_CONNECT]));
    }
}
