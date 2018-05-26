<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JavaScript;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Traits\Controllers\JavascriptInjection;

class StatisticsController extends Controller
{
    use JavascriptInjection;

    private $allocationRepository;

    private $databaseRepository;

    private $keyProviderService;

    private $eggRepository;

    private $nodeRepository;

    private $serverRepository;

    private $userRepository;

    function __construct(
        AllocationRepositoryInterface $allocationRepository,
        DatabaseRepositoryInterface $databaseRepository,
        DaemonKeyProviderService $keyProviderService,
        EggRepositoryInterface $eggRepository,
        NodeRepositoryInterface $nodeRepository,
        ServerRepositoryInterface $serverRepository,
        UserRepositoryInterface $userRepository
    )
    {
        $this->allocationRepository = $allocationRepository;
        $this->databaseRepository = $databaseRepository;
        $this->keyProviderService = $keyProviderService;
        $this->eggRepository = $eggRepository;
        $this->nodeRepository = $nodeRepository;
        $this->serverRepository = $serverRepository;
        $this->userRepository = $userRepository;
    }

    public function index(Request $request)
    {
        $servers = $this->serverRepository->all();
        $serversCount = count($servers);
        $nodes = $this->nodeRepository->all();
        $nodesCount = count($nodes);
        $usersCount = $this->userRepository->count();
        $eggsCount = $this->eggRepository->count();
        $databasesCount = $this->databaseRepository->count();
        $totalServerRam = DB::table('servers')->sum('memory');
        $totalNodeRam = DB::table('nodes')->sum('memory');
        $totalServerDisk = DB::table('servers')->sum('disk');
        $totalNodeDisk = DB::table('nodes')->sum('disk');
        $totalAllocations = $this->allocationRepository->count();
        $suspendedServersCount = $this->serverRepository->getBuilder()->where('suspended', true)->count();

        $tokens = [];
        foreach ($nodes as $node) {
            $tokens[$node->id] = $node->daemonSecret;
        }

        $this->injectJavascript([
            'servers' => $servers,
            'serverCount' => $serversCount,
            'suspendedServers' => $suspendedServersCount,
            'totalServerRam' => $totalServerRam,
            'totalNodeRam' => $totalNodeRam,
            'totalServerDisk' => $totalServerDisk,
            'totalNodeDisk' => $totalNodeDisk,
            'nodes' => $nodes,
            'tokens' => $tokens,
        ]);

        return view('admin.statistics', [
            'serversCount' => $serversCount,
            'nodesCount' => $nodesCount,
            'usersCount' => $usersCount,
            'eggsCount' => $eggsCount,
            'totalServerRam' => $totalServerRam,
            'databasesCount' => $databasesCount,
            'totalNodeRam' => $totalNodeRam,
            'totalNodeDisk' => $totalNodeDisk,
            'totalServerDisk' => $totalServerDisk,
            'totalAllocations' => $totalAllocations,
        ]);
    }

}
