<?php

namespace Pterodactyl\Http\Controllers\Api\Remote;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\EnvironmentService;

class EggInstallController extends Controller
{
    /**
     * EggInstallController constructor.
     */
    public function __construct(private EnvironmentService $environment)
    {
    }

    /**
     * Handle request to get script and installation information for a server
     * that is being created on the node.
     *
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $node = $request->attributes->get('node');

        /** @var Server $server */
        $server = Server::with('egg.scriptFrom')
            ->where('uuid', $uuid)
            ->where('node_id', $node->id)
            ->firstOrFail();

        $egg = $server->getRelation('egg');

        return response()->json([
            'scripts' => [
                'install' => !$egg->copy_script_install ? null : str_replace(["\r\n", "\n", "\r"], "\n", $egg->copy_script_install),
                'privileged' => $egg->script_is_privileged,
            ],
            'config' => [
                'container' => $egg->copy_script_container,
                'entry' => $egg->copy_script_entry,
            ],
            'env' => $this->environment->handle($server),
        ]);
    }
}
