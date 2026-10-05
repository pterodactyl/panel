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
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\DeleteSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\GetSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\StoreSubuserRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Subusers\UpdateSubuserRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Transformers\Api\Client\SubuserTransformer;
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
     *
     * @return ApiPayload
     */
    #[Endpoint('List server subusers', 'Returns subusers assigned to the server.')]
    #[ResponseFromTransformer(SubuserTransformer::class, Subuser::class, description: 'Server subusers returned.', collection: true, resourceKey: 'server_subuser')]
    public function index(GetSubuserRequest $request, Server $server): array
    {
        return Fractal::collection($server->subusers)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toResponseArray();
    }

    /**
     * Returns a single subuser associated with this server instance.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get server subuser', 'Returns one subuser assignment for the server.')]
    #[ResponseFromTransformer(SubuserTransformer::class, Subuser::class, description: 'Server subuser returned.', resourceKey: 'server_subuser')]
    public function view(GetSubuserRequest $request): array
    {
        $subuser = $request->attributes->get('subuser');

        return Fractal::item($subuser)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create a new subuser for the given server.
     *
     *
     * @return ApiPayload
     *
     * @throws ServerSubuserExistsException
     * @throws UserIsServerOwnerException
     * @throws Throwable
     */
    #[Endpoint('Create server subuser', 'Assigns an existing or new user as a subuser on the server.')]
    #[ResponseFromTransformer(SubuserTransformer::class, Subuser::class, description: 'Server subuser created.', resourceKey: 'server_subuser')]
    #[ScribeResponse(self::SUBUSER_EXISTS_ERROR, status: 400, description: 'The user is already assigned to the server or is the server owner.')]
    public function store(StoreSubuserRequest $request, CreatesSubusers $subusers, Server $server): array
    {
        $email = $request->string('email')->toString();
        $permissions = $request->permissions();
        $response = $subusers->create(
            $server,
            $email,
            $permissions,
        );

        if ($response->user->wasRecentlyCreated) {
            Activity::event('user:user.create')
                ->subject($response->user)
                ->property(['email' => $response->user->email, 'username' => $response->user->username, 'admin' => $response->user->root_admin])
                ->log();
        }

        Activity::event('server:subuser.create')
            ->subject($response->user)
            ->property(['email' => $email, 'permissions' => $permissions])
            ->log();

        return Fractal::item($response)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toResponseArray();
    }

    /**
     * Update a given subuser in the system for the server.
     *
     *
     * @return ApiPayload
     */
    #[Endpoint('Update server subuser', 'Updates permissions for a server subuser and revokes their SFTP access tokens.')]
    #[ResponseFromTransformer(SubuserTransformer::class, Subuser::class, description: 'Server subuser updated.', resourceKey: 'server_subuser')]
    public function update(UpdateSubuserRequest $request, UpdatesSubusers $subusers, Server $server): array
    {
        $subuser = $request->subuser();
        $permissions = $request->permissions();

        $current = $subuser->permissions;
        $requested = $permissions;
        sort($current);
        sort($requested);

        // Only touch the database and Wings when the permissions actually change.
        if ($requested !== $current) {
            $subuser = $subusers->update($server, $subuser, $permissions);

            Activity::event('server:subuser.update')
                ->subject($subuser->user)
                ->property([
                    'email' => $subuser->user->email,
                    'old' => $current,
                    'new' => $requested,
                    'revoked' => true,
                ])
                ->log();
        }

        return Fractal::item($subuser)
            ->transformWith($this->getTransformer(SubuserTransformer::class))
            ->toResponseArray();
    }

    /**
     * Removes a subusers from a server's assignment.
     */
    #[Endpoint('Delete server subuser', 'Removes a subuser assignment from the server and revokes SFTP access tokens.')]
    #[ScribeResponse(status: 204, description: 'Server subuser removed.')]
    public function delete(DeleteSubuserRequest $request, DeletesSubusers $subusers, Server $server): JsonResponse
    {
        $subuser = $request->subuser();

        $subusers->delete($server, $subuser);

        Activity::event('server:subuser.delete')
            ->subject($subuser->user)
            ->property('email', $subuser->user->email)
            ->property('revoked', true)
            ->log();

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
