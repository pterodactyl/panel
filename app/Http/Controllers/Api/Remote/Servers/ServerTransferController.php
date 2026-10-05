<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Remote\Servers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Pterodactyl\Contracts\Transfers\CompletesTransfers;
use Pterodactyl\Contracts\Transfers\FailsTransfers;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Remote\RemoteRequestNode;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ServerTransfer;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class ServerTransferController extends Controller
{
    /**
     * The daemon notifies us about a transfer failure. Either node may report it.
     *
     * @throws Throwable
     */
    public function failure(Request $request, FailsTransfers $transfers, string $uuid): JsonResponse
    {
        $transfer = $this->activeTransfer($uuid);

        $node = RemoteRequestNode::get($request);
        throw_if(! $node->is($transfer->newNode) && ! $node->is($transfer->oldNode), HttpForbiddenException::class, 'Requesting node does not have permission to access this server.');

        $transfers->fail($transfer);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * The daemon notifies us about a transfer success. Only the new node may report it.
     *
     * @throws Throwable
     */
    public function success(Request $request, CompletesTransfers $transfers, string $uuid): JsonResponse
    {
        $transfer = $this->activeTransfer($uuid);

        $node = RemoteRequestNode::get($request);
        throw_unless($node->is($transfer->newNode), HttpForbiddenException::class, 'Requesting node does not have permission to access this server.');

        $transfers->complete($transfer);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    private function activeTransfer(string $uuid): ServerTransfer
    {
        $server = Server::query()->with('node')->whereUuidOrShort($uuid)->firstOrFail();

        return $server->transfer ?? throw new ConflictHttpException('Server is not being transferred.');
    }
}
