<?php
namespace Pterodactyl\Http\Controllers\Admin\Jexactyl;

use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Extensions\Spatie\Fractalistic\Fractal;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Transformers\Api\Client\StatsTransformer;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Traits\Controllers\PlainJavascriptInjection;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class IndexController extends Controller
{
    use PlainJavascriptInjection;

    private Repository $cache;
    private Fractal $fractal;
    private DaemonServerRepository $repository;
    private SoftwareVersionService $versionService;
    private NodeRepositoryInterface $nodeRepository;
    private ServerRepositoryInterface $serverRepository;

    public function __construct(
        Fractal $fractal,
        Repository $cache,
        DaemonServerRepository $repository,
        SoftwareVersionService $versionService,
        NodeRepositoryInterface $nodeRepository,
        ServerRepositoryInterface $serverRepository,
    ) {
        $this->cache = $cache;
        $this->fractal = $fractal;
        $this->repository = $repository;
        $this->nodeRepository = $nodeRepository;
        $this->versionService = $versionService;
        $this->serverRepository = $serverRepository;
    }

    public function index(): View
    {
        $nodes = $this->nodeRepository->all();
        $servers = $this->serverRepository->all();
        $allocations = DB::table('allocations')->count();
        $suspended = DB::table('servers')->where('status', 'suspended')->count();

        $memoryUsed = 0;
        $memoryTotal = 0;
        $diskUsed = 0;
        $diskTotal = 0;

        foreach ($nodes as $node) {
            $stats = $this->nodeRepository->getUsageStatsRaw($node);

            $memoryUsed += $stats['memory']['value'];
            $memoryTotal += $stats['memory']['max'];
            $diskUsed += $stats['disk']['value'];
            $diskTotal += $stats['disk']['max'];
        }

		$status = [];

		foreach($servers as $server) {
			$key = 'resources:' . $server->uuid;
            try {
                $state = $this->cache->remember($key, Carbon::now()->addSeconds(60), function () use ($server) {
                    return $this->repository->setServer($server)->getDetails();
                });
            } catch (DaemonConnectionException $ex) {
                $state = ['state' => 'unavailable'];
            }

			$status[$server->uuid] = $this->fractal->item($state)
				->transformWith(StatsTransformer::class)
				->toArray();
		}

        $this->injectJavascript([
            'servers' => $servers,
            'diskUsed' => $diskUsed,
            'diskTotal' => $diskTotal,
            'serverstatus' => $status,
            'suspended' => $suspended,
            'memoryUsed' => $memoryUsed,
            'memoryTotal' => $memoryTotal,
        ]);

        return view('admin.jexactyl.index', [
            'version' => $this->versionService,
            'servers' => $servers,
            'allocations' => $allocations,
            'used' => [
                'memory' => $memoryUsed,
                'disk' => $memoryTotal,
            ],
            'available' => [
                'memory' => $memoryTotal,
                'disk' => $diskTotal,
            ],
        ]);
    }
} 