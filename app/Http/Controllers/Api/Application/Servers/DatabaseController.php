<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Application\Servers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Databases\CreatesDatabases;
use Pterodactyl\Contracts\Databases\DeletesDatabases;
use Pterodactyl\Contracts\Databases\RotatesDatabasePasswords;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;
use Pterodactyl\Http\Requests\Api\Application\Servers\Databases\GetServerDatabaseRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\Databases\GetServerDatabasesRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\Databases\ServerDatabaseWriteRequest;
use Pterodactyl\Http\Requests\Api\Application\Servers\Databases\StoreServerDatabaseRequest;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Application\ServerDatabaseTransformer;
use Throwable;

#[Group('Application API', 'Root administrator endpoints for managing panel resources using application API tokens.')]
#[Subgroup('Server Databases', 'Create, retrieve, rotate, and delete databases attached to servers.')]
class DatabaseController extends ApplicationApiController
{
    private const array DATABASE_LIMIT_ERROR = [
        'errors' => [
            [
                'code' => 'TooManyDatabasesException',
                'status' => '400',
                'detail' => 'Operation aborted: creating a new database would put this server over the defined limit.',
            ],
        ],
    ];

    /**
     * Return a listing of all databases currently available to a single
     * server.
     *
     * @return ApiPayload
     */
    #[Endpoint('List server databases', 'Returns all databases attached to a server.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "password" and "host" when the key can read database hosts.', required: false, example: 'password,host', enum: ['password', 'host', 'password,host'])]
    #[ResponseFromTransformer(ServerDatabaseTransformer::class, Database::class, description: 'Server databases returned.', collection: true, factoryStates: ['withServerAndHost'], resourceKey: 'server_database')]
    public function index(GetServerDatabasesRequest $request, Server $server): array
    {
        return Fractal::collection($server->databases)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toResponseArray();
    }

    /**
     * Return a single server database.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get server database', 'Returns a single database attached to a server.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "password" and "host" when the key can read database hosts.', required: false, example: 'password,host', enum: ['password', 'host', 'password,host'])]
    #[ResponseFromTransformer(ServerDatabaseTransformer::class, Database::class, factoryStates: ['withServerAndHost'], resourceKey: 'server_database')]
    public function view(GetServerDatabaseRequest $request, Server $server, Database $database): array
    {
        return Fractal::item($database)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toResponseArray();
    }

    /**
     * Reset the password for a specific server database.
     *
     * @throws Throwable
     */
    #[Endpoint('Reset server database password', 'Rotates the password for a database attached to a server.')]
    #[ScribeResponse(status: 204, description: 'Database password reset.')]
    public function resetPassword(ServerDatabaseWriteRequest $request, RotatesDatabasePasswords $databasePasswordService, Server $server, Database $database): JsonResponse
    {
        $databasePasswordService->rotate($database);

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Create a new database on the Panel for a given server.
     *
     * @throws Throwable
     */
    #[Endpoint('Create server database', 'Creates a database for a server on a configured database host.')]
    #[ResponseFromTransformer(ServerDatabaseTransformer::class, Database::class, status: 201, description: 'Database created.', factoryStates: ['withServerAndHost'], resourceKey: 'server_database', meta: ['resource' => 'https://panel.example.test/api/application/servers/1/databases/1'])]
    #[ScribeResponse(self::DATABASE_LIMIT_ERROR, status: 400, description: 'The server has reached its configured database limit.')]
    public function store(StoreServerDatabaseRequest $request, CreatesDatabases $createDatabase, Server $server): JsonResponse
    {
        $database = $createDatabase->create($server, array_merge($request->payload(), [
            'database' => $request->databaseName(),
        ]));

        return Fractal::item($database)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->addMeta([
                'resource' => route('api.application.servers.databases.view', [
                    'server' => $server->id,
                    'database' => $database->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Handle a request to delete a specific server database from the Panel.
     */
    #[Endpoint('Delete server database', 'Deletes a database attached to a server.')]
    #[ScribeResponse(status: 204, description: 'Database deleted.')]
    public function delete(ServerDatabaseWriteRequest $request, DeletesDatabases $deleteDatabase, Server $server, Database $database): Response
    {
        $deleteDatabase->delete($database);

        return response('', 204);
    }
}
