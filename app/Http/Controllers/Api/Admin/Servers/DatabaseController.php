<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Databases\CreatesDatabases;
use Pterodactyl\Contracts\Databases\DeletesDatabases;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\Databases\GetDatabasesRequest;
use Pterodactyl\Http\Requests\Api\Admin\Servers\Databases\ServerDatabaseWriteRequest;
use Pterodactyl\Http\Requests\Api\Admin\Servers\Databases\StoreDatabaseRequest;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\DatabaseName;
use Pterodactyl\Transformers\Api\Admin\ServerDatabaseTransformer;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Server Databases', 'Create, inspect, and delete databases assigned to a server.')]
class DatabaseController extends AdminApiController
{
    public const array INSTALL_STATE_ERROR = [
        'errors' => [
            [
                'code' => 'HttpException',
                'status' => '403',
                'detail' => 'Access to this resource is not allowed due to the current installation state.',
            ],
        ],
    ];

    /**
     * List server databases.
     *
     * @return ApiPayload
     */
    #[Endpoint('List server databases', 'Returns all databases assigned to a server.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "password".', required: false, example: 'password', enum: ['password'])]
    #[ResponseFromTransformer(ServerDatabaseTransformer::class, Database::class, description: 'Server databases returned.', collection: true, resourceKey: 'server_database')]
    #[ScribeResponse(self::INSTALL_STATE_ERROR, status: 403, description: 'The server is not installed.')]
    public function index(GetDatabasesRequest $request, Server $server): array
    {
        $this->assertServerInstalled($server);

        return Fractal::collection($server->databases)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toResponseArray();
    }

    /**
     * Show server database.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get server database', 'Returns a single database assigned to a server.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports "password".', required: false, example: 'password', enum: ['password'])]
    #[ResponseFromTransformer(ServerDatabaseTransformer::class, Database::class, description: 'Server database returned.', resourceKey: 'server_database')]
    #[ScribeResponse(self::INSTALL_STATE_ERROR, status: 403, description: 'The server is not installed.')]
    public function show(GetDatabasesRequest $request, Server $server, Database $database): array
    {
        $this->assertServerInstalled($server);
        $this->assertDatabaseBelongsToServer($server, $database);

        return Fractal::item($database)
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->toResponseArray();
    }

    /**
     * Create server database.
     */
    #[Endpoint('Create server database', 'Creates a database for a server and returns the generated password.')]
    #[ResponseFromTransformer(ServerDatabaseTransformer::class, Database::class, status: 201, description: 'Server database created.', resourceKey: 'server_database', meta: ['resource' => 'https://panel.example.com/api/admin/servers/1/databases/Lk3jx4pQ'], include: ['password'])]
    #[ScribeResponse(self::INSTALL_STATE_ERROR, status: 403, description: 'The server is not installed.')]
    public function store(StoreDatabaseRequest $request, CreatesDatabases $createDatabase, Server $server): JsonResponse
    {
        $this->assertServerInstalled($server);

        $data = $request->payload();

        $database = $createDatabase->create($server, [
            'database_host_id' => $data['database_host_id'],
            'database' => DatabaseName::generateUnique($data['database'], $server->id),
            'remote' => $data['remote'],
            'max_connections' => $data['max_connections'] ?? null,
        ]);

        Activity::event('admin:server-database.create')
            ->subject($database)
            ->property('name', $database->database)
            ->log();

        return Fractal::item($database)
            ->parseIncludes(['password'])
            ->transformWith($this->getTransformer(ServerDatabaseTransformer::class))
            ->addMeta([
                'resource' => route('api.admin.servers.databases.view', [
                    'server' => $server->id,
                    'database' => $database->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Delete server database.
     */
    #[Endpoint('Delete server database', 'Deletes a database assigned to a server.')]
    #[ScribeResponse(status: 204, description: 'Server database deleted.')]
    #[ScribeResponse(self::INSTALL_STATE_ERROR, status: 403, description: 'The server is not installed.')]
    public function destroy(ServerDatabaseWriteRequest $request, DeletesDatabases $deleteDatabase, Server $server, Database $database): Response
    {
        $this->assertServerInstalled($server);
        $this->assertDatabaseBelongsToServer($server, $database);

        $deleteDatabase->delete($database);

        Activity::event('admin:server-database.delete')
            ->subject($database)
            ->property('name', $database->database)
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Ensure database belongs to server.
     */
    private function assertDatabaseBelongsToServer(Server $server, Database $database): void
    {
        if ($database->server_id !== $server->id) {
            throw (new ModelNotFoundException)->setModel(Database::class);
        }
    }

    private function assertServerInstalled(Server $server): void
    {
        throw_unless($server->isInstalled(), HttpException::class, Response::HTTP_FORBIDDEN, 'Access to this resource is not allowed due to the current installation state.');
    }
}
