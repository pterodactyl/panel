<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Facades\Activity;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Eggs\EggConfigurationService;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Http\Resources\Wings\ServerConfigurationCollection;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;

class ServerDetailsController extends Controller
{
    /**
     * ServerConfigurationController constructor.
     */
    public function __construct(
        protected ConnectionInterface $connection,
        private ServerRepository $repository,
        private ServerConfigurationStructureService $configurationStructureService,
        private EggConfigurationService $eggConfigurationService,
    ) {
    }

    /**
     * Returns details about the server that allows Wings to self-recover and ensure
     * that the state of the server matches the Panel at all times.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        Assert::isInstanceOf($node = $request->attributes->get('node'), Node::class);

        $server = $this->repository->getByUuid($uuid);
        $transfer = $server->transfer;

        // If the server is being transferred allow either node to request information about
        // the server. If the server is not being transferred only the target node is allowed
        // to fetch these details.
        $valid = $transfer
            ? $node->id === $transfer->old_node || $node->id === $transfer->new_node
            : $node->id === $server->node_id;

        if (! $valid) {
            throw new HttpForbiddenException('Requesting node does not have permission to access this server.');
        }

        return new JsonResponse([
            'settings' => $this->configurationStructureService->handle($server),
            'process_configuration' => $this->eggConfigurationService->handle($server),
        ]);
    }

    /**
     * Lists all servers with their configurations that are assigned to the requesting node.
     */
    public function list(Request $request): ServerConfigurationCollection
    {
        /** @var Node $node */
        $node = $request->attributes->get('node');

        // Avoid run-away N+1 SQL queries by preloading the relationships that are used
        // within each of the services called below.
        $servers = Server::query()->with('allocations', 'egg', 'mounts', 'variables', 'location')
            ->where('node_id', $node->id)
            // If you don't cast this to a string you'll end up with a stringified per_page returned in
            // the metadata, and then Wings will panic crash as a result.
            ->paginate((int) $request->input('per_page', 50));

        return new ServerConfigurationCollection($servers);
    }

    /**
     * Resets the state of all servers on the node to be normal. This is triggered
     * when Wings restarts and is useful for ensuring that any servers on the node
     * do not get incorrectly stuck in installing/restoring from backup states since
     * a Wings reboot would completely stop those processes.
     *
     * @throws \Throwable
     */
    public function resetState(Request $request): JsonResponse
    {
        $node = $request->attributes->get('node');

        // Get all the servers that are currently marked as restoring from a backup
        // on this node that do not have a failed backup tracked in the audit logs table
        // as well.
        //
        // For each of those servers we'll track a new audit log entry to mark them as
        // failed and then update them all to be in a valid state.
        $servers = Server::query()
            ->with([
                'activity' => fn ($builder) => $builder
                    ->where('activity_logs.event', 'server:backup.restore-started')
                    ->latest('timestamp'),
            ])
            ->where('node_id', $node->id)
            ->where('status', Server::STATUS_RESTORING_BACKUP)
            ->get();

        $this->connection->transaction(function () use ($node, $servers) {
            /** @var Server $server */
            foreach ($servers as $server) {
                /** @var \Pterodactyl\Models\ActivityLog|null $activity */
                $activity = $server->activity->first();
                if (!is_null($activity)) {
                    if ($subject = $activity->subjects->where('subject_type', 'backup')->first()) {
                        // Just create a new audit entry for this event and update the server state
                        // so that power actions, file management, and backups can resume as normal.
                        Activity::event('server:backup.restore-failed')
                            ->subject($server, $subject->subject)
                            ->property('name', $subject->subject->name) // @phpstan-ignore property.notFound
                            ->log();
                    }
                }
            }

            // Update any server marked as installing or restoring as being in a normal state
            // at this point in the process.
            Server::query()->where('node_id', $node->id)
                ->whereIn('status', [Server::STATUS_INSTALLING, Server::STATUS_RESTORING_BACKUP])
                ->update(['status' => null]);
        });

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
