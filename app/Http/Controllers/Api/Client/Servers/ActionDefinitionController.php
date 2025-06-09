<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Http\Requests\Api\Client\Servers\Hooks\ViewHooksRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\ActionDefinition;

class ActionDefinitionController extends Controller
{
    public function index(ViewHooksRequest $request, Server $server) {
        return response()->json(["data"=>ActionDefinition::all()]);
    }

}
