<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Http\Requests\Api\Client\Servers\EditServerRequest;

class ServerEditService
{
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    /**
     * Updates the requested instance with new limits.
     *
     * @throws DisplayException
     */
    public function handle(EditServerRequest $request, Server $server)
    {
        $user = $request->user();
        $amount = $request->input('amount');
        $resource = $request->input('resource');

        if ($user->id != $server->owner_id) {
            throw new DisplayException('You do not own this server, therefore you cannot make changes.');
        }

        $this->verify($request, $server, $user);

        $server->update([$resource => $this->toServer($resource, $server) + $amount]);
        $user->update(['store_' . $resource => $this->toUser($resource, $user) - $amount]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Ensure that the server is not going past the limits
     * for minimum resources per-container.
     *
     * @throws DisplayException
     */
    protected function verify(EditServerRequest $request, Server $server, User $user)
    {
        $amount = $request->input('amount');
        $resource = $request->input('resource');
        $limit = $this->settings->get('jexactyl::store:limit:' . $resource);

        // Check if the amount requested goes over defined limits.
        if (($amount + $this->toServer($resource, $server)) > $limit) {
            throw new DisplayException('You cannot add this resource because an administrator has set a maximum limit.');
        }

        // Verify baseline limits. We don't want servers with -4% CPU.
        if ($this->toServer($resource, $server) <= $this->toMin($resource) && $amount < 0) {
            throw new DisplayException('You cannot go below this amount.');
        }

        // Verify that the user has the resource in their account.
        if ($this->toUser($resource, $user) < $amount) {
            throw new DisplayException('You do not have the resources available to make this change.');
        }
    }

     /**
      * Gets the minimum value for a specific resource.
      *
      * @throws DisplayException
      */
     protected function toMin(string $resource): int
     {
         return match ($resource) {
             'cpu' => 50,
             'allocation_limit' => 1,
             'disk', 'memory' => 1024,
             'backup_limit', 'database_limit' => 0,
             default => throw new DisplayException('Unable to parse resource type')
         };
     }

     /**
      * Get the requested resource type and transform it
      * so it can be used in a database statement.
      *
      * @throws DisplayException
      */
     protected function toUser(string $resource, User $user): int
     {
         return match ($resource) {
             'cpu' => $user->store_cpu,
             'disk' => $user->store_disk,
             'memory' => $user->store_memory,
             'backup_limit' => $user->store_backups,
             'allocation_limit' => $user->store_ports,
             'database_limit' => $user->store_databases,
             default => throw new DisplayException('Unable to parse resource type')
         };
     }

    /**
     * Get the requested resource type and transform it
     * so it can be used in a database statement.
     *
     * @throws DisplayException
     */
    protected function toServer(string $resource, Server $server): int
    {
        return match ($resource) {
            'cpu' => $server->cpu,
            'disk' => $server->disk,
            'memory' => $server->memory,
            'backup_limit' => $server->backup_limit,
            'database_limit' => $server->database_limit,
            'allocation_limit' => $server->allocation_limit,
            default => throw new DisplayException('Unable to parse resource type')
        };
    }
}
