<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Nodes;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Illuminate\Http\JsonResponse;
use Illuminate\Cache\Repository as Cache;
use Pterodactyl\Repositories\Wings\DaemonConfigurationRepository;
use Pterodactyl\Http\Controllers\Api\Application\ApplicationApiController;

class NodeInformationController extends ApplicationApiController
{
    private Cache $cache;
    private DaemonConfigurationRepository $repository;

    public function __construct(Cache $cache, DaemonConfigurationRepository $repository)
    {
        parent::__construct();

        $this->cache = $cache;
        $this->repository = $repository;
    }

    /**
     * Returns system information from the node.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function __invoke(Request $request, Node $node)
    {
        $data = $this->cache
            ->tags(['nodes'])
            ->remember($node->uuid, Carbon::now()->addSeconds(30), function () use ($node) {
                return $this->repository->setNode($node)->getSystemInformation();
            });

        return new JsonResponse([
            'version' => $data['version'] ?? null,
            'system' => [
                'type' => Str::title($data['os'] ?? 'Unknown'),
                'arch' => $data['architecture'] ?? null,
                'release' => $data['kernel_version'] ?? null,
                'cpus' => $data['cpu_count'] ?? null,
            ],
        ]);
    }
}
