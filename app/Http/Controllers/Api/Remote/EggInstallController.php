<?php

namespace App\Http\Controllers\Api\Remote;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Servers\EnvironmentService;
use App\Contracts\Repository\ServerRepositoryInterface;

class EggInstallController extends Controller
{
    /**
     * @var \App\Services\Servers\EnvironmentService
     */
    private $environment;

    /**
     * @var \App\Contracts\Repository\ServerRepositoryInterface
     */
    private $repository;

    /**
     * EggInstallController constructor.
     *
     * @param \App\Services\Servers\EnvironmentService            $environment
     * @param \App\Contracts\Repository\ServerRepositoryInterface $repository
     */
    public function __construct(EnvironmentService $environment, ServerRepositoryInterface $repository)
    {
        $this->environment = $environment;
        $this->repository = $repository;
    }

    /**
     * Handle request to get script and installation information for a server
     * that is being created on the node.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $uuid
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \App\Exceptions\Repository\RecordNotFoundException
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $node = $request->attributes->get('node');

        /** @var \App\Models\Server $server */
        $server = $this->repository->findFirstWhere([
            ['uuid', '=', $uuid],
            ['node_id', '=', $node->id],
        ]);

        $this->repository->loadEggRelations($server);
        $egg = $server->getRelation('egg');

        return response()->json([
            'scripts' => [
                'install' => ! $egg->copy_script_install ? null : str_replace(["\r\n", "\n", "\r"], "\n", $egg->copy_script_install),
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
