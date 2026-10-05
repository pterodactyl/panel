<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pterodactyl\Contracts\Nodes\ResetsServerStates;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Remote\RemoteRequestNode;
use Pterodactyl\Http\Resources\Wings\ServerConfigurationCollection;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Eggs\EggConfigurationService;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;
use Throwable;

class ServerDetailsController extends Controller
{
    /**
     * Returns details about the server that allows Wings to self-recover and ensure
     * that the state of the server matches the Panel at all times.
     */
    public function __invoke(Request $request, EggConfigurationService $eggConfiguration, ServerConfigurationStructureService $configuration, string $uuid): JsonResponse
    {
        $node = RemoteRequestNode::get($request);

        $server = Server::query()->with('node')->whereUuidOrShort($uuid)->firstOrFail();
        $transfer = $server->transfer;

        // If the server is being transferred allow either node to request information about
        // the server. If the server is not being transferred only the target node is allowed
        // to fetch these details.
        $valid = $transfer
            ? $node->id === $transfer->old_node || $node->id === $transfer->new_node
            : $node->id === $server->node_id;

        throw_unless($valid, HttpForbiddenException::class, 'Requesting node does not have permission to access this server.');

        return new JsonResponse([
            'settings' => $configuration->handle($server),
            'process_configuration' => $eggConfiguration->handle($server),
        ]);
    }

    /**
     * Lists all servers with their configurations that are assigned to the requesting node.
     */
    public function list(Request $request, ServerConfigurationStructureService $configuration, EggConfigurationService $eggConfiguration): JsonResponse
    {
        $node = RemoteRequestNode::get($request);

        // Avoid run-away N+1 SQL queries by preloading the relationships that are used
        // within each of the services called below.
        $servers = Server::query()->with('allocations', 'egg.variables', 'egg.configFrom', 'serverVariables', 'mounts', 'location')
            ->where('node_id', $node->id)
            // If you don't cast this to a string you'll end up with a stringified per_page returned in
            // the metadata, and then Wings will panic crash as a result.
            ->paginate($request->integer('per_page', 50));

        return (new ServerConfigurationCollection($servers, $eggConfiguration, $configuration))->response();
    }

    /**
     * Resets the state of all servers on the node to be normal. This is triggered
     * when Wings restarts and is useful for ensuring that any servers on the node
     * do not get incorrectly stuck in installing/restoring from backup states since
     * a Wings reboot would completely stop those processes.
     *
     * @throws Throwable
     */
    public function resetState(Request $request, ResetsServerStates $states): JsonResponse
    {
        $states->reset(RemoteRequestNode::get($request));

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
