<?php

namespace Pterodactyl\Services\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Servers\EditServerRequest;

class ServerEditService
{
    private SettingsRepositoryInterface $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Updates the requested instance with new limits.
     */
    public function handle(EditServerRequest $request, Server $server)
    {
        $user = $request->user();
        $amount = $request->input('amount');
        $resource = $request->input('resource');

        $check = $this->verifyResources($request, $server);
        if ($check == false) return;

        $server->update([
            $resource => $this->getServerResource($request, $server) + $amount,
        ]);

        $user->update([
            'store_' . $this->toStr($request->input('resource')) => $this->getUserResource($request) - $amount,
        ]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    protected function toStr(string $res): string
    {
        return (string) $res;
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
            case 'allocation_limit':
                return $request->user()->store_ports;
            case 'backup_limit':
                return $request->user()->store_backups;
            case 'database_limit':
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
    protected function verifyResources(EditServerRequest $request, Server $server): bool
    {
        $resource = $request->input('resource');
        $amount = $request->input('amount');
        $user = $request->user();

        $cpu_limit = $this->settings->get('jexactyl::store:limit:cpu');
        $memory_limit = $this->settings->get('jexactyl::store:limit:memory');
        $disk_limit = $this->settings->get('jexactyl::store:limit:disk');
        $allocation_limit = $this->settings->get('jexactyl::store:limit:port');
        $backup_limit = $this->settings->get('jexactyl::store:limit:backup');
        $database_limit = $this->settings->get('jexactyl::store:limit:database');

        if ($resource == 'cpu' && (($amount + $server->cpu) > $cpu_limit)) return false;
        if ($resource == 'memory' && (($amount + $server->memory) > $memory_limit)) return false;
        if ($resource == 'disk' && (($amount + $server->disk) > $disk_limit)) return false;
        if ($resource == 'allocation_limit' && (($amount + $server->allocation_limit) > $allocation_limit)) return false;
        if ($resource == 'backup_limit' && (($amount + $server->backup_limit) > $backup_limit)) return false;
        if ($resource == 'database_limit' && (($amount + $server->database_limit) > $database_limit)) return false;

        // Check that the server's limits are acceptable.
        if ($resource == 'cpu' && $server->cpu <= 50 && $amount < 0) return false;
        if ($resource == 'memory' && $server->memory <= 1024 && $amount < 0) return false;
        if ($resource == 'disk' && $server->disk <= 1024 && $amount < 0) return false;
        if ($resource == 'allocation_limit' && $server->allocation_limit <= 1 && $amount < 0) return false;
        if ($resource == 'backup_limit' && $server->backup_limit <= 0 && $amount < 0) return false;
        if ($resource == 'database_limit' && $server->database_limit <= 0 && $amount < 0) return false;


        // Check whether the user has enough resource in their account.
        if ($resource == 'cpu' && $user->store_cpu < $amount) return false;
        if ($resource == 'memory' && $user->store_memory < $amount) return false;
        if ($resource == 'disk' && $user->store_disk < $amount) return false;
        if ($resource == 'allocation_limit' && $user->store_ports < $amount) return false;
        if ($resource == 'backup_limit' && $user->store_backups < $amount) return false;
        if ($resource == 'database_limit' && $user->store_databases < $amount) return false;

        // Return true if all checked.
        return true;
    }
}