<?php

namespace Pterodactyl\Services\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Requests\Api\Client\Servers\EditServerRequest;

class ServerEditService
{
    /**
     * Updates the requested instance with new limits.
     */
    public function handle(EditServerRequest $request, Server $server)
    {
        $user = $request->user();
        $amount = $request->input('amount');
        $resource = $request->input('resource');

        $this->verifyServerResources($request, $server);

        $server->update([
            $resource => $this->getServerResource($request, $server) + $amount,
        ]);

        $user->update([
            'store_'.$resource => $this->getUserResource($request) - $amount,
        ]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get the requested resource type and transform it
     * so it can be used in a database statement.
     *
     * @throws DisplayException
     */
    protected function getUserResource(EditServerRequest $request)
    {
        switch ($request->input('resource')) {
            case 'cpu':
                return $request->user()->store_cpu;
            case 'memory':
                return $request->user()->store_memory;
            case 'disk':
                return $request->user()->store_disk;
            case 'slots':
                return $request->user()->store_slots;
            case 'ports':
                return $request->user()->store_ports;
            case 'backups':
                return $request->user()->store_backups;
            case 'databases':
                return $request->user()->store_databases;
            default:
                throw new DisplayException('Unable to parse resource type.');
        }
    }

    /**
     * Get the requested resource type and transform it
     * so it can be used in a database statement.
     *
     * @throws DisplayException
     */
    protected function getServerResource(EditServerRequest $request, Server $server)
    {
        switch ($request->input('resource')) {
            case 'cpu':
                return $server->cpu;
            case 'memory':
                return $server->memory;
            case 'disk':
                return $server->disk;
            case 'allocation_limit':
                return $server->allocation_limit;
            case 'backup_limit':
                return $server->backup_limit;
            case 'database_limit':
                return $server->database_limit;
            default:
                throw new DisplayException('Unable to parse resource type.');
        }
    }

    /**
     * Ensure that the server is not going past the limits
     * for minimum resources per-container.
     * 
     * @throws DisplayException
     */
    protected function verifyServerResources(EditServerRequest $request, Server $server)
    {
        $resource = $request->input('resource');
        $amount = $request->input('amount');
    
        if ($resource == 'cpu' && $server->cpu <= 50 && $amount < 0) throw new DisplayException('Cannot have less than 50% CPU assigned to server.');
        if ($resource == 'memory' && $server->memory <= 1024 && $amount < 0) throw new DisplayException('Cannot have less than 1GB RAM assigned to server.');
        if ($resource == 'disk' && $server->disk <= 1024 && $amount < 0) throw new DisplayException('Cannot have less than 1GB RAM assigned to server.');
        if ($resource == 'allocation_limit' && $server->allocation_limit <= 1 && $amount < 0) throw new DisplayException('Cannot have less than 1 network allocation assigned to server.');
        if ($resource == 'backup_limit' && $server->backup_limit <= 0 && $amount < 0) throw new DisplayException('Cannot have less than 1 backup slot assigned to server.');
        if ($resource == 'database_limit' && $server->database_limit <= 0 && $amount < 0) throw new DisplayException('Cannot have less than 1 database slot assigned to server.');
    }
}