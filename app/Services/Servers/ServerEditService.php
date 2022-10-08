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

        if ($user->id != $server->owner_id) return;
        $verify = $this->verify($request, $server, $user);
        if (!$verify) return;

        $server->update([
            $resource => $this->getServerResource($request, $server) + $amount,
        ]);

        $user->update([
            'store_' . (string) $request->input('resource') => $this->toUser($request, $user) - $amount,
        ]);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Ensure that the server is not going past the limits
     * for minimum resources per-container.
     *
     * @throws DisplayException
     */
    protected function verify(EditServerRequest $request, Server $server, User $user): bool
    {
        $amount = $request->input('amount');
        $resource = $request->input('resource');

        $limit = $this->settings->get('jexactyl::store:limit:' . $resource);

        // Check if the amount requested goes over defined limits.
        if (($amount + $this->toServer($resource, $server)) > $limit) return false;
        // Verify baseline limits. We don't want servers with -4% CPU.
        if ($this->toServer($resource, $server) <= $this->toMin($resource) && $amount < 0) return false;
        // Verify that the user has the resource in their account.
        if ($this->toUser($resource, $user) < $amount) return false;

        // Return true if all checked.
        return true;
    }

    /**
     * Gets the minimum value for a specific resource.
     *
     * @throws DisplayException
     */
     protected function toMin(EditServerRequest $request): int
     {
         return match($request->input('resource')) {
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
     protected function toUser(EditServerRequest $request, User $user): int
     {
         return match ($request->input('resource')) {
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
    protected function toServer(EditServerRequest $request, Server $server): ?int
    {
        return match ($request->input('resource')) {
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
