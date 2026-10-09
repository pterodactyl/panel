<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Databases\DeletesDatabases;
use Pterodactyl\Contracts\Databases\DeploysServerDatabases;
use Pterodactyl\Contracts\Databases\RotatesDatabasePasswords;
use Pterodactyl\Exceptions\Service\Database\DatabaseClientFeatureNotEnabledException;
use Pterodactyl\Exceptions\Service\Database\TooManyDatabasesException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\DeleteDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\GetDatabasesRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\RotatePasswordRequest;
use Pterodactyl\Http\Requests\Api\Client\Servers\Databases\StoreDatabaseRequest;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\DatabaseTransformer;
use Throwable;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Databases', 'Create, list, rotate, and delete databases for an accessible server.')]
class DatabaseController extends ClientApiController
{
    private const array DATABASE_LIMIT_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'Cannot create additional databases on this server: limit has been reached.',
            ],
        ],
    ];

    /**
     * Return all the databases that belong to the given server.
     *
     * @return ApiPayload
     */
    #[Endpoint('List server databases', 'Returns databases attached to a server visible to the authenticated user.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "password" when the user can view database passwords.', required: false, example: 'password', enum: ['password'])]
    #[ResponseFromTransformer(DatabaseTransformer::class, Database::class, description: 'Server databases returned.', collection: true, resourceKey: 'server_database')]
    public function index(GetDatabasesRequest $request, Server $server): array
    {
        return Fractal::collection($server->databases)
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create a new database for the given server and return it.
     *
     *
     * @return ApiPayload
     *
     * @throws Throwable
     * @throws TooManyDatabasesException
     * @throws DatabaseClientFeatureNotEnabledException
     */
    #[Endpoint('Create server database', 'Creates a database for a server on an automatically selected database host.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "password" when the user can view database passwords.', required: false, example: 'password', enum: ['password'])]
    #[ResponseFromTransformer(DatabaseTransformer::class, Database::class, description: 'Database created.', resourceKey: 'server_database', include: ['password'])]
    #[ScribeResponse(self::DATABASE_LIMIT_ERROR, status: 400, description: 'The server has reached its configured database limit or client database creation is disabled.')]
    public function store(StoreDatabaseRequest $request, DeploysServerDatabases $deployDatabase, Server $server): array
    {
        $database = $deployDatabase->deploy($server, $request->payload());

        Activity::event('server:database.create')->subject($database)->property('name', $database->database)->log();

        return Fractal::item($database)
            ->parseIncludes(['password'])
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toResponseArray();
    }

    /**
     * Rotates the password for the given server model and returns a fresh instance to
     * the caller.
     *
     *
     * @return ApiPayload
     *
     * @throws Throwable
     */
    #[Endpoint('Rotate server database password', 'Rotates the password for a database attached to a server and returns the updated database.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "password" when the user can view database passwords.', required: false, example: 'password', enum: ['password'])]
    #[ResponseFromTransformer(DatabaseTransformer::class, Database::class, description: 'Database password rotated.', resourceKey: 'server_database', include: ['password'])]
    public function rotatePassword(RotatePasswordRequest $request, RotatesDatabasePasswords $passwordService, Server $server, Database $database): array
    {
        // The action commits its own transaction around the remote user change, so the
        // activity is logged afterwards instead of wrapping a change it cannot undo.
        $passwordService->rotate($database);

        Activity::event('server:database.rotate-password')
            ->subject($database)
            ->property('name', $database->database)
            ->log();

        return Fractal::item($database->refresh())
            ->parseIncludes(['password'])
            ->transformWith($this->getTransformer(DatabaseTransformer::class))
            ->toResponseArray();
    }

    /**
     * Removes a database from the server.
     */
    #[Endpoint('Delete server database', 'Deletes a database attached to a server.')]
    #[ScribeResponse(status: 204, description: 'Database deleted.')]
    public function delete(DeleteDatabaseRequest $request, DeletesDatabases $deleteDatabase, Server $server, Database $database): Response
    {
        $deleteDatabase->delete($database);

        Activity::event('server:database.delete')
            ->subject($database)
            ->property('name', $database->database)
            ->log();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
