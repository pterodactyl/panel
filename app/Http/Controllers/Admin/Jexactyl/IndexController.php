<?php
namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Cache\Repository;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Spatie\Fractalistic\Fractal;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Transformers\Api\Client\StatsTransformer;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Traits\Controllers\PlainJavascriptInjection;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class IndexController extends Controller
{
    use PlainJavascriptInjection;

    private Repository $cache;
    private Fractal $fractal;
    private DaemonServerRepository $repository;
    private SoftwareVersionService $versionService;
    private NodeRepositoryInterface $nodeRepository;
    private ServerRepositoryInterface $serverRepository;
    private AllocationRepositoryInterface $allocationRepository;

    public function __construct(
        Fractal $fractal,
        Repository $cache,
        DaemonServerRepository $repository,
        SoftwareVersionService $versionService,
        NodeRepositoryInterface $nodeRepository,
        ServerRepositoryInterface $serverRepository,
        AllocationRepositoryInterface $allocationRepository
    ) {
        $this->cache = $cache;
        $this->fractal = $fractal;
        $this->repository = $repository;
        $this->nodeRepository = $nodeRepository;
        $this->versionService = $versionService;
        $this->serverRepository = $serverRepository;
        $this->allocationRepository = $allocationRepository;
    }

    public function index(): View
    {
        $servers = $this->serverRepository->all();
        $nodes = $this->nodeRepository->all();
        $totalAllocations = $this->allocationRepository->count();
        $suspendedServersCount = $this->serverRepository->getSuspendedServersCount();

        $totalServerRam = 0;
        $totalNodeRam = 0;
        $totalServerDisk = 0;
        $totalNodeDisk = 0;

        foreach ($nodes as $node) {
            $stats = $this->nodeRepository->getUsageStatsRaw($node);
            $totalServerRam += $stats['memory']['value'];
            $totalNodeRam += $stats['memory']['max'];
            $totalServerDisk += $stats['disk']['value'];
            $totalNodeDisk += $stats['disk']['max'];
        }

		$serverstatus = [];
		foreach($servers as $server) {
			$key = "resources:{$server->uuid}";
			$stats = $this->cache->remember($key, Carbon::now()->addSeconds(20), function () use ($server) {
				return $this->repository->setServer($server)->getDetails();
			});

			$serverstatus[$server->uuid] = $this->fractal->item($stats)
				->transformWith(StatsTransformer::class)
				->toArray();
		}

        $this->injectJavascript([
            'servers' => $servers,
            'suspendedServers' => $suspendedServersCount,
            'totalServerRam' => $totalServerRam,
            'totalNodeRam' => $totalNodeRam,
            'totalServerDisk' => $totalServerDisk,
            'totalNodeDisk' => $totalNodeDisk,
            'nodes' => $nodes,
            'serverstatus' => $serverstatus,
        ]);

        return view('admin.jexactyl.index', [
            'version' => $this->versionService,
            'servers' => $servers,
            'used' => [
                'ram' => $totalServerRam,
                'disk' => $totalServerDisk,
            ],
            'available' => [
                'ram' => $totalNodeRam,
                'disk' => $totalNodeDisk,
                'allocations' => $totalAllocations,
            ],
        ]);
    }
} 