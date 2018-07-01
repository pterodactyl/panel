<?php

namespace Pterodactyl\Http\Controllers\Daemon;

use Cache;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Events\Server\Installed as ServerInstalled;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

class ActionController extends Controller
{
    /**
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    private $eventDispatcher;

    /**
     * ActionController constructor.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $eventDispatcher
     */
    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Handles install toggle request from daemon.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markInstall(Request $request)
    {
        $server = Server::where('uuid', $request->input('server'))->with('node')->first();
        if (! $server) {
            return response()->json([
                'error' => 'No server by that ID was found on the system.',
            ], 422);
        }

        $hmac = $request->input('signed');
        $status = $request->input('installed');

        if (! hash_equals(base64_decode($hmac), hash_hmac('sha256', $server->uuid, $server->node->daemonSecret, true))) {
            return response()->json([
                'error' => 'Signed HMAC was invalid.',
            ], 403);
        }

        $server->installed = ($status === 'installed') ? 1 : 2;
        $server->save();

        // Only fire event if server installed successfully.
        if ($server->installed === 1) {
            $this->eventDispatcher->dispatch(new ServerInstalled($server));
        }

        return response()->json([]);
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
