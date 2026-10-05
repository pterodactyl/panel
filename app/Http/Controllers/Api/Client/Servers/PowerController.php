<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\SendsServerPower;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\SendPowerRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Support\JsonValueGuard;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Overview', 'Inspect and control an accessible server.')]
class PowerController extends ClientApiController
{
    /**
     * Send a power action to a server.
     */
    #[Endpoint('Send power action', 'Sends a power signal to the server through Wings.')]
    #[ScribeResponse(status: 204, description: 'Power signal accepted by Wings.')]
    public function index(SendPowerRequest $request, SendsServerPower $power, Server $server): Response
    {
        $signal = JsonValueGuard::string($request->validated('signal'));
        $power->send($server, $signal);

        Activity::event(mb_strtolower("server:power.{$signal}"))->log();

        return $this->returnNoContent();
    }
}
