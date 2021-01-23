<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Traits\Controllers\PlainJavascriptInjection;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class StatisticsController extends Controller
{
    use PlainJavascriptInjection;

    private $allocationRepository;

    private $databaseRepository;

    private $eggRepository;

    private $nodeRepository;

    private $serverRepository;

    private $userRepository;

    public function __construct(
        AllocationRepositoryInterface $allocationRepository,
        DatabaseRepositoryInterface $databaseRepository,
        EggRepositoryInterface $eggRepository,
        NodeRepositoryInterface $nodeRepository,
        ServerRepositoryInterface $serverRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->allocationRepository = $allocationRepository;
        $this->databaseRepository = $databaseRepository;
        $this->eggRepository = $eggRepository;
        $this->nodeRepository = $nodeRepository;
        $this->serverRepository = $serverRepository;
        $this->userRepository = $userRepository;
    }

    public function index()
    {
        throw new NotFoundHttpException();
        $servers = $this->serverRepository->all();
        $nodes = $this->nodeRepository->all();
        $usersCount = $this->userRepository->count();
        $eggsCount = $this->eggRepository->count();
        $databasesCount = $this->databaseRepository->count();
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

        $tokens = [];
        foreach ($nodes as $node) {
            $tokens[$node->id] = decrypt($node->daemon_token);
        }

        $this->injectJavascript([
            'servers' => $servers,
            'suspendedServers' => $suspendedServersCount,
            'totalServerRam' => $totalServerRam,
            'totalNodeRam' => $totalNodeRam,
            'totalServerDisk' => $totalServerDisk,
            'totalNodeDisk' => $totalNodeDisk,
            'nodes' => $nodes,
            'tokens' => $tokens,
        ]);

        return view('admin.statistics', [
            'servers' => $servers,
            'nodes' => $nodes,
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
