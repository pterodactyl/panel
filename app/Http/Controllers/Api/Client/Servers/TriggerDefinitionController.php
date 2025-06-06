<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Client\Servers\Hooks\ViewHooksRequest;
use Pterodactyl\Models\Hook;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\TriggerDefinition;

class TriggerDefinitionController extends Controller
{
    public function index(ViewHooksRequest $request, Server $server) {
        return response()->json(["data"=>TriggerDefinition::all()]);
    }

}
