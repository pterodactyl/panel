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

        $verify = $this->verify($request);
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
     * Get the requested resource type and transform it
     * so it can be used in a database statement.
     *
     * @throws DisplayException
     */
     protected function toUser(EditServerRequest $request, User $user)
     {
       switch ($resource, $request->input('resource')) {
         case 'cpu':
           $obj = $user->store_cpu;
         case 'memory':
           $obj = $user->store_memory;
         case 'disk':
           $obj = $user->store_disk;
         case 'allocation_limit':
           $obj = $user->store_ports;
         case 'backup_limit':
           $obj = $user->store_backups;
         case 'database_limit':
           $obj = $user->store_databases;
         default:
           throw new DisplayException('unable to parse resource type');

         return $obj;
       }
     }

    /**
     * Get the requested resource type and transform it
     * so it can be used in a database statement.
     *
     * @throws DisplayException
     */
    protected function toServer(EditServerRequest $request, Server $server)
    {
        switch ($request->input('resource')) {
            case 'cpu':
                $obj = $server->cpu;
            case 'memory':
                $obj = $server->memory;
            case 'disk':
                $obj = $server->disk;
            case 'allocation_limit':
                $obj = $server->allocation_limit;
            case 'backup_limit':
                $obj = $server->backup_limit;
            case 'database_limit':
                $obj = $server->database_limit;
            default:
                throw new DisplayException('unable to parse resource type');

            return $obj;
        }
    }

    /**
     * Ensure that the server is not going past the limits
     * for minimum resources per-container.
     *
     * @throws DisplayException
     */
    protected function verify(EditServerRequest $request): bool
    {
        $amount = $request->input('amount');
        $resource = $request->input('resource');

        foreach ($resource as $r) {
          $limit = $this->settings->get('jexactyl::store:limit:' . $r);

          // Check if the amount requested goes over defined limits.
          if (($amount + $this->toServer($r)) > $limit) return false;
          // Verify baseline limits. We don't want servers with -4% CPU.
          if ($this->toServer($r) <= $this->toMin($r) && $amount < 0) return false;
          // Verify that the user has the resource in their account.
          if ($this->toUser($) < $amount) return false;
        }

        // Return true if all checked.
        return true;
    }
}
