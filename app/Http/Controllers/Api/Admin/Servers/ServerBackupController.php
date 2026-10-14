<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\QueryParam;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\Backups\ListBackupsRequest;
use Pterodactyl\Http\Resources\Api\Admin\BackupResource;
use Pterodactyl\Http\Resources\Api\ApiResourceCollection;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Server Backups', 'Inspect and lock backups assigned to a server.')]
class ServerBackupController extends AdminApiController
{
    /**
     * List server backups.
     */
    #[Endpoint('List server backups', 'Returns a paginated list of backups assigned to a server.')]
    #[QueryParam('filter[uuid]', 'string', 'Filter backups by UUID.', example: '1b19cf3f-2f89-4f88-a81e-321e7fe326bc')]
    #[QueryParam('filter[name]', 'string', 'Filter backups by name.', example: 'Before Update')]
    #[QueryParam('sort', 'string', 'Sort backups by id, bytes, or created_at. Prefix with a hyphen for descending order.', example: '-created_at')]
    #[QueryParam('per_page', 'integer', 'Results to return per page.', example: 25)]
    #[ResponseFromResource(BackupResource::class, Backup::class, description: 'Server backups returned.', collection: true, resourceKey: 'backup', paginate: 25)]
    public function index(ListBackupsRequest $request, Server $server): ApiResourceCollection
    {
        $backups = QueryBuilder::for($server->backups())
            ->allowedFilters(['uuid', 'name'])
            ->allowedSorts(['id', 'bytes', 'created_at'])
            ->paginate($request->perPage(25));

        return BackupResource::collection($backups);
    }
}
