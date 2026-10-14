<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Subusers\CreatesSubusers;
use Pterodactyl\Contracts\Subusers\DeletesSubusers;
use Pterodactyl\Contracts\Subusers\UpdatesSubusers;
use Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException;
use Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\DeleteSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\GetSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\StoreSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\UpdateSubuserRequest;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Http\Resources\Api\Client\SubuserResource;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Throwable;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Subusers', 'Create, view, update, and remove server subusers.')]
class SubuserController extends ClientApiController
{
    private const array SUBUSER_EXISTS_ERROR = [
        'errors' => [
            [
                'code' => 'ServerSubuserExistsException',
                'status' => '400',
                'detail' => 'A user with that email address is already assigned as a subuser for this server.',
            ],
        ],
    ];

    /**
     * Return the users associated with this server instance.
     */
    #[Endpoint('List server subusers', 'Returns subusers assigned to the server.')]
    #[ResponseFromResource(SubuserResource::class, Subuser::class, description: 'Server subusers returned.', collection: true, resourceKey: 'server_subuser')]
    public function index(GetSubuserRequest $request, Server $server): ApiResourceCollection
    {
        return SubuserResource::collection($server->subusers);
    }

    /**
     * Returns a single subuser associated with this server instance.
     */
    #[Endpoint('Get server subuser', 'Returns one subuser assignment for the server.')]
    #[ResponseFromResource(SubuserResource::class, Subuser::class, description: 'Server subuser returned.', resourceKey: 'server_subuser')]
    public function view(GetSubuserRequest $request): SubuserResource
    {
        $subuser = $request->attributes->get('subuser');

        return SubuserResource::make($subuser);
    }

    /**
     * Create a new subuser for the given server.
     *
     * @throws ServerSubuserExistsException
     * @throws UserIsServerOwnerException
     * @throws Throwable
     */
    #[Endpoint('Create server subuser', 'Assigns an existing or new user as a subuser on the server.')]
    #[ResponseFromResource(SubuserResource::class, Subuser::class, description: 'Server subuser created.', resourceKey: 'server_subuser')]
    #[ScribeResponse(self::SUBUSER_EXISTS_ERROR, status: 400, description: 'The user is already assigned to the server or is the server owner.')]
    public function store(StoreSubuserRequest $request, CreatesSubusers $subusers, Server $server): SubuserResource
    {
        $response = $subusers->create($server, $request->string('email')->toString(), $request->permissions());

        return SubuserResource::make($response);
    }

    /**
     * Update a given subuser in the system for the server.
     */
    #[Endpoint('Update server subuser', 'Updates permissions for a server subuser and revokes their SFTP access tokens.')]
    #[ResponseFromResource(SubuserResource::class, Subuser::class, description: 'Server subuser updated.', resourceKey: 'server_subuser')]
    public function update(UpdateSubuserRequest $request, UpdatesSubusers $subusers, Server $server): SubuserResource
    {
        $subuser = $subusers->update($server, $request->subuser(), $request->permissions());

        return SubuserResource::make($subuser);
    }

    /**
     * Removes a subusers from a server's assignment.
     */
    #[Endpoint('Delete server subuser', 'Removes a subuser assignment from the server and revokes SFTP access tokens.')]
    #[ScribeResponse(status: 204, description: 'Server subuser removed.')]
    public function delete(DeleteSubuserRequest $request, DeletesSubusers $subusers, Server $server): JsonResponse
    {
        $subusers->delete($server, $request->subuser());

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
