<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\AuditLog;
use Pterodactyl\Models\Permission;
use Illuminate\Auth\Access\AuthorizationException;
use Pterodactyl\Transformers\Api\Client\AuditLogTransformer;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\Logs\GetLogsRequest;

class LogsController extends ClientApiController
{
    /**
     * LogsController constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Returns all the logs for a given server instance in a paginated
     * result set.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(GetLogsRequest $request, Server $server): array
    {
        $limit = min($request->query('per_page') ?? 20, 50);

        return array_reverse($this->fractal->collection($server->audits()->paginate($limit))
            ->transformWith($this->getTransformer(AuditLogTransformer::class))
            ->addMeta([
                'log_count' => $server->audits()->count(),
            ])
            ->toArray());
    }
}