<?php

namespace App\Http\Controllers\Daemon;

use Cache;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Repositories\Eloquent\ServerRepository;
use App\Events\Server\Installed as ServerInstalled;
use App\Exceptions\Repository\RecordNotFoundException;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

class ActionController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    private $eventDispatcher;
    /**
     * @var \App\Repositories\Eloquent\ServerRepository
     */
    private $repository;

    /**
     * ActionController constructor.
     *
     * @param \App\Repositories\Eloquent\ServerRepository $repository
     * @param \Illuminate\Contracts\Events\Dispatcher             $eventDispatcher
     */
    public function __construct(ServerRepository $repository, EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->repository = $repository;
    }

    /**
     * Handles install toggle request from daemon.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \App\Exceptions\Model\DataValidationException
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function markInstall(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\Server $server */
            $server = $this->repository->findFirstWhere([
                'uuid' => $request->input('server'),
            ]);
        } catch (RecordNotFoundException $exception) {
            return JsonResponse::create([
                'error' => 'No server by that ID was found on the system.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $server->relationLoaded('node')) {
            $server->load('node');
        }

        $hmac = $request->input('signed');
        $status = $request->input('installed');

        if (! hash_equals(base64_decode($hmac), hash_hmac('sha256', $server->uuid, $server->getRelation('node')->daemonSecret, true))) {
            return JsonResponse::create([
                'error' => 'Signed HMAC was invalid.',
            ], Response::HTTP_FORBIDDEN);
        }

        $this->repository->update($server->id, [
            'installed' => ($status === 'installed') ? 1 : 2,
        ], true, true);

        // Only fire event if server installed successfully.
        if ($status === 'installed') {
            $this->eventDispatcher->dispatch(new ServerInstalled($server));
        }

        // Don't use a 204 here, the daemon is hard-checking for a 200 code.
        return JsonResponse::create([]);
    }

    /**
     * Handles configuration data request from daemon.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $token
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function configuration(Request $request, $token)
    {
        $nodeId = Cache::pull('Node:Configuration:' . $token);
        if (is_null($nodeId)) {
            return response()->json(['error' => 'token_invalid'], 403);
        }

        $node = Node::findOrFail($nodeId);

        // Manually as getConfigurationAsJson() returns it in correct format already
        return response($node->getConfigurationAsJson())->header('Content-Type', 'text/json');
    }
}
