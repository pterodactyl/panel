<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use JavaScript;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{

    public function index(Request $request)
    {
        $servers = Server::all();
        $serversCount = count($servers);
        $nodesCount = Node::count();
        $usersCount = User::count();
        $eggsCount = Egg::count();
        $databasesCount = Database::count();
        $totalServerRam = DB::table('servers')->sum('memory');
        $totalNodeRam = DB::table('nodes')->sum('memory');
        $totalServerDisk = DB::table('servers')->sum('disk');
        $totalNodeDisk = DB::table('nodes')->sum('disk');
        $totalAllocations = Allocation::count();

        $suspendedServersCount = Server::where('suspended', true)->count();

        Javascript::put([
            'servers' => Server::all(),
            'serverCount' => $serversCount,
            'suspendedServers' => $suspendedServersCount,
            'totalServerRam' => $totalServerRam,
            'totalNodeRam' => $totalNodeRam,
            'totalServerDisk' => $totalServerDisk,
            'totalNodeDisk' => $totalNodeDisk,
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
