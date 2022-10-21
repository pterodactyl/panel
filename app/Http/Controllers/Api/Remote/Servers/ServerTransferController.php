<?php

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Allocation;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\ServerTransfer;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Nodes\NodeJWTService;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Repositories\Wings\DaemonTransferRepository;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class ServerTransferController extends Controller
{
    /**
     * ServerTransferController constructor.
     */
    public function __construct(
        private ConnectionInterface $connection,
        private ServerRepository $repository,
        private DaemonServerRepository $daemonServerRepository,
        private DaemonTransferRepository $daemonTransferRepository,
        private NodeJWTService $jwtService
    ) {
    }

    /**
     * The daemon notifies us about the archive status.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     * @throws \Throwable
     */
    public function archive(Request $request, string $uuid): JsonResponse
    {
        $server = $this->repository->getByUuid($uuid);

        // Unsuspend the server and don't continue the transfer.
        if (!$request->input('successful')) {
            return $this->processFailedTransfer($server->transfer);
        }

        $this->connection->transaction(function () use ($server) {
            // This token is used by the new node the server is being transferred to. It allows
            // that node to communicate with the old node during the process to initiate the
            // actual file transfer.
            $token = $this->jwtService
                ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
                ->setSubject($server->uuid)
                ->handle($server->node, $server->uuid, 'sha256');

            // Update the archived field on the transfer to make clients connect to the websocket
            // on the new node to be able to receive transfer logs.
            $server->transfer->forceFill(['archived' => true])->saveOrFail();

            // On the daemon transfer repository, make sure to set the node after the server
            // because setServer() tells the repository to use the server's node and not the one
            // we want to specify.
            $this->daemonTransferRepository
                ->setServer($server)
                ->setNode($server->transfer->newNode)
                ->notify($server, $token);
        });

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * The daemon notifies us about a transfer failure.
     *
     * @throws \Throwable
     */
    public function failure(string $uuid): JsonResponse
    {
        $server = $this->repository->getByUuid($uuid);

        return $this->processFailedTransfer($server->transfer);
    }

    /**
     * The daemon notifies us about a transfer success.
     *
     * @throws \Throwable
     */
    public function success(string $uuid): JsonResponse
    {
        $server = $this->repository->getByUuid($uuid);
        $transfer = $server->transfer;

        /** @var \Pterodactyl\Models\Server $server */
        $server = $this->connection->transaction(function () use ($server, $transfer) {
            $allocations = array_merge([$transfer->old_allocation], $transfer->old_additional_allocations);

            // Remove the old allocations for the server and re-assign the server to the new
            // primary allocation and node.
            Allocation::query()->whereIn('id', $allocations)->update(['server_id' => null]);
            $server->update([
                'allocation_id' => $transfer->new_allocation,
                'node_id' => $transfer->new_node,
            ]);

            $server = $server->fresh();
            $server->transfer->update(['successful' => true]);

            return $server;
        });

        // Delete the server from the old node making sure to point it to the old node so
        // that we do not delete it from the new node the server was transferred to.
        try {
            $this->daemonServerRepository
                ->setServer($server)
                ->setNode($transfer->oldNode)
                ->delete();
        } catch (DaemonConnectionException $exception) {
            Log::warning($exception, ['transfer_id' => $server->transfer->id]);
        }

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Release all the reserved allocations for this transfer and mark it as failed in
     * the database.
     *
     * @throws \Throwable
     */
    protected function processFailedTransfer(ServerTransfer $transfer): JsonResponse
    {
        $this->connection->transaction(function () use (&$transfer) {
            $transfer->forceFill(['successful' => false])->saveOrFail();

            $allocations = array_merge([$transfer->new_allocation], $transfer->new_additional_allocations);
            Allocation::query()->whereIn('id', $allocations)->update(['server_id' => null]);
        });

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
