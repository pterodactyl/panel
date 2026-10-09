<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\DatabaseHosts;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use PDOException;
use Pterodactyl\Contracts\Databases\CreatesDatabaseHosts;
use Pterodactyl\Contracts\Databases\DeletesDatabaseHosts;
use Pterodactyl\Contracts\Databases\UpdatesDatabaseHosts;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Scribe\Attributes\ExtensionFieldsParam;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\DatabaseHosts\DeleteDatabaseHostRequest;
use Pterodactyl\Http\Requests\Api\Admin\DatabaseHosts\GetDatabaseHostRequest;
use Pterodactyl\Http\Requests\Api\Admin\DatabaseHosts\GetDatabaseHostsRequest;
use Pterodactyl\Http\Requests\Api\Admin\DatabaseHosts\StoreDatabaseHostRequest;
use Pterodactyl\Http\Requests\Api\Admin\DatabaseHosts\UpdateDatabaseHostRequest;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Admin\DatabaseHostTransformer;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;
use UnexpectedValueException;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Database Hosts', 'Create and manage database hosts available to servers.')]
class DatabaseHostController extends AdminApiController
{
    private const array DATABASE_HOST_DATABASE_EXAMPLE = [
        'object' => 'database',
        'attributes' => [
            'server' => [
                'id' => 1,
                'name' => 'Survival',
            ],
            'name' => 's1_database',
            'username' => 'u1_database',
            'connections_from' => '%',
            'max_connections' => 0,
        ],
    ];

    private const array DATABASE_LIST_EXAMPLE = [
        'object' => 'list',
        'data' => [
            self::DATABASE_HOST_DATABASE_EXAMPLE,
        ],
        'meta' => [
            'pagination' => [
                'total' => 1,
                'count' => 1,
                'per_page' => 50,
                'current_page' => 1,
                'total_pages' => 1,
                'links' => [],
            ],
        ],
    ];

    private const array CONNECTION_ERROR = [
        'errors' => [
            [
                'code' => 'DisplayException',
                'status' => '400',
                'detail' => 'There was an error while trying to connect to the host or while executing a query: "connection refused"',
            ],
        ],
    ];

    /**
     * List database hosts.
     *
     * @return ApiPayload
     */
    #[Endpoint('List database hosts', 'Returns a paginated list of database hosts.')]
    #[QueryParam('page', 'integer', 'Page number to retrieve.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[QueryParam('filter[name]', 'string', 'Filter hosts by name.', required: false, example: 'Primary')]
    #[QueryParam('filter[host]', 'string', 'Filter hosts by address or hostname.', required: false, example: '127.0.0.1')]
    #[QueryParam('sort', 'string', 'Sort hosts by ID, name, or creation date. Prefix with a hyphen for descending order.', required: false, example: '-created_at')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports node and databases.', required: false, example: 'node')]
    #[ResponseFromTransformer(DatabaseHostTransformer::class, DatabaseHost::class, description: 'Database hosts returned.', collection: true, resourceKey: 'database_host', paginate: [IlluminatePaginatorAdapter::class, 50])]
    public function index(GetDatabaseHostsRequest $request): array
    {
        $query = QueryBuilder::for(DatabaseHost::query())
            ->allowedFilters(['name', 'host'])
            ->allowedSorts(['id', 'name', 'created_at']);

        $hosts = $query->getEloquentBuilder()
            ->with('node')
            ->withCount('databases')
            ->paginate($request->perPage());

        return Fractal::collection($hosts)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class))
            ->toResponseArray();
    }

    /**
     * Show database host.
     *
     * @return ApiPayload
     */
    #[Endpoint('Get database host', 'Returns a single database host by ID.')]
    #[QueryParam('include', 'string', 'Comma-separated relationships to include. Supports node and databases.', required: false, example: 'node')]
    #[ResponseFromTransformer(DatabaseHostTransformer::class, DatabaseHost::class, description: 'Database host returned.', resourceKey: 'database_host', include: ['node', 'databases'])]
    public function show(GetDatabaseHostRequest $request, DatabaseHost $databaseHost): array
    {
        $databaseHost->loadCount('databases');
        $databaseHost->load('node');

        return Fractal::item($databaseHost)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class)->withExtensionFields())
            ->toResponseArray();
    }

    /**
     * List databases associated with a database host.
     *
     * @return ApiPayload
     */
    #[Endpoint('List database host databases', 'Returns a paginated list of server databases assigned to a database host.')]
    #[QueryParam('page', 'integer', 'Page number to retrieve.', required: false, example: 1)]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', required: false, example: 50)]
    #[ScribeResponse(self::DATABASE_LIST_EXAMPLE, description: 'Databases returned.')]
    #[ResponseField('attributes.max_connections', 'integer', example: 0, nullable: true)]
    public function databases(GetDatabaseHostRequest $request, DatabaseHost $databaseHost): array
    {
        $databases = $databaseHost->databases()
            ->with('server')
            ->paginate($request->perPage());

        return Fractal::collection($databases, resourceName: 'database')
            ->transformWith(function (Database $database): array {
                $server = $database->getRelation('server');
                throw_unless($server instanceof Server, UnexpectedValueException::class, 'A database response requires its server relation.');

                return [
                    'server' => [
                        'id' => $server->id,
                        'name' => $server->name,
                    ],
                    'name' => $database->database,
                    'username' => $database->username,
                    'connections_from' => $database->remote,
                    'max_connections' => $database->max_connections,
                ];
            })
            ->toResponseArray();
    }

    /**
     * Create database host.
     */
    #[Endpoint('Create database host', 'Creates a database host after validating that the panel can connect to it.')]
    #[ResponseFromTransformer(DatabaseHostTransformer::class, DatabaseHost::class, status: 201, description: 'Database host created.', resourceKey: 'database_host', meta: ['resource' => 'https://panel.example.com/api/admin/database-hosts/1'])]
    #[ScribeResponse(self::CONNECTION_ERROR, status: 400, description: 'The panel could not connect to the database host.')]
    #[ExtensionFieldsParam]
    public function store(StoreDatabaseHostRequest $request, CreatesDatabaseHosts $createHost): JsonResponse
    {
        try {
            $host = $createHost->create($request->payload());
        } catch (Exception $exception) {
            $this->handleConnectionException($exception);
        }

        $host->load('node')->loadCount('databases');

        Activity::event('admin:database-host.create')
            ->subject($host)
            ->property('name', $host->name)
            ->log();

        return Fractal::item($host)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class)->withExtensionFields())
            ->addMeta([
                'resource' => route('api.admin.database-hosts.view', [
                    'databaseHost' => $host->id,
                ]),
            ])
            ->respond(Response::HTTP_CREATED);
    }

    /**
     * Update database host.
     *
     * @return ApiPayload
     */
    #[Endpoint('Update database host', 'Updates a database host after validating that the panel can connect to the supplied host details.')]
    #[ResponseFromTransformer(DatabaseHostTransformer::class, DatabaseHost::class, description: 'Database host updated.', resourceKey: 'database_host')]
    #[ScribeResponse(self::CONNECTION_ERROR, status: 400, description: 'The panel could not connect to the database host.')]
    #[ExtensionFieldsParam]
    public function update(UpdateDatabaseHostRequest $request, UpdatesDatabaseHosts $updateHost, DatabaseHost $databaseHost): array
    {
        try {
            $host = $updateHost->update($databaseHost, $request->payload());
        } catch (Exception $exception) {
            $this->handleConnectionException($exception);
        }

        $host->load('node')->loadCount('databases');

        Activity::event('admin:database-host.update')
            ->subject($host)
            ->property('name', $host->name)
            ->log();

        return Fractal::item($host)
            ->transformWith($this->getTransformer(DatabaseHostTransformer::class)->withExtensionFields())
            ->toResponseArray();
    }

    /**
     * Delete database host.
     */
    #[Endpoint('Delete database host', 'Deletes a database host when no server databases depend on it.')]
    #[ScribeResponse(status: 204, description: 'Database host deleted.')]
    public function destroy(DeleteDatabaseHostRequest $request, DeletesDatabaseHosts $deleteHost, DatabaseHost $databaseHost): Response
    {
        Activity::event('admin:database-host.delete')
            ->subject($databaseHost)
            ->property('name', $databaseHost->name)
            ->log();

        $deleteHost->delete($databaseHost);

        return $this->returnNoContent();
    }

    private function handleConnectionException(Throwable $exception): never
    {
        if ($exception instanceof PDOException || $exception->getPrevious() instanceof PDOException) {
            throw new DisplayException(sprintf('There was an error while trying to connect to the host or while executing a query: "%s"', $exception->getMessage()), $exception);
        }

        throw $exception;
    }
}
