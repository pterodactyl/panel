<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Node;

class DeployController extends Controller
{

    private $creationService;

    public function __construct(ServerCreationService $creationService) 
    {
        $this->creationService = $creationService;
    }
    
    public function index(Request $request)
    {
        return view('base.deploy')->with('nests', Nest::get());
    }

    public function submit(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'nest' => 'required|exists:nests,id',
            'egg' => 'required|exists:eggs,id',
        ]);
        $nest = Nest::find($request->nest);
        $egg = $nest->eggs()->find($request->egg);
        if (!$nest || !$egg) return redirect()->back();
        $request->validate([
            'ram' => 'required|numeric|min:256|max:'.$nest->max_memory*1024,
            'disk' => 'required|numeric|min:1|max:'.$nest->max_disk,
        ]);
        $allocation = $this->getAllocationId();
        $cost = ($request->ram / 1024) * $nest->memory_monthly_cost;
        $cost += $request->disk * $nest->disk_monthly_cost;
        if (!$allocation) return redirect()->back()->withErrors(trans('base.errors.deploy.full'));
        if ($request->user()->balance < $cost) return redirect()->back()->withErrors(trans('base.errors.deploy.founds', ['cost' => $cost]));
        $data = [
            'name' => $request->name,
            'owner_id' => $request->user()->id,
            'egg_id' => $egg->id,
            'nest_id' => $nest->id,
            'allocation_id' => $allocation,
            'environment' => [],
            'memory' => $request->ram,
            'disk' => $request->disk*1024,
            'cpu' => $nest->cpu_limit,
            'swap' => 0,
            'io' => 500,
            'database_limit' => $nest->database_limit,
            'allocation_limit' => $nest->allocations_limit,
            'image' => $egg->docker_image,
            'startup' => $egg->startup,
            'start_on_completion' => true,
        ];
        foreach ($egg->variables()->where('user_editable', 1)->get() as $var) {
            $key = "v{$nest->id}-{$egg->id}-{$var->env_variable}";
            $data['environment'][$var->env_variable] = $request->get($key, $var->default);
            $request->validate([
                $key => $var->rules
            ]);
        }
        $server = $this->creationService->handle($data);
        $server->monthly_cost = $cost;
        $server->save();
        return redirect()->route('index');
    }

    private function getAllocationId($attempt = 0)
    {
        if ($attempt > 6) return null;
        $node = Node::where('nodes.public', true)->where('nodes.maintenance_mode', false)
            ->whereRaw('nodes.memory > (SELECT IFNULL(SUM(servers.memory), 0) FROM servers WHERE servers.node_id = nodes.id)')
            ->whereRaw('nodes.disk > (SELECT IFNULL(SUM(servers.disk), 0) FROM servers WHERE servers.node_id = nodes.id)')->inRandomOrder()->first();
        if (!$node) return false;
        $allocation = $node->allocations()->where('server_id', null)->inRandomOrder()->first();
        if (!$allocation) return getAllocationId($attempt+1);
        return $allocation->id;
    }
}
