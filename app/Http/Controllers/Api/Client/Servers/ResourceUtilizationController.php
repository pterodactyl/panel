<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response as ScribeResponse;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Servers\ReadsServerState;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\GetServerRequest;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Client\StatsTransformer;

#[Group('Client API', 'Endpoints authenticated as a panel user using a client API token.')]
#[Subgroup('Server Overview', 'Inspect and control an accessible server.')]
class ResourceUtilizationController extends ClientApiController
{
    private const array STATS_EXAMPLE = [
        'object' => 'stats',
        'attributes' => [
            'current_state' => 'running',
            'is_suspended' => false,
            'resources' => [
                'memory_bytes' => 536870912,
                'cpu_absolute' => 12.5,
                'disk_bytes' => 2147483648,
                'network_rx_bytes' => 102400,
                'network_tx_bytes' => 204800,
                'uptime' => 3600000,
            ],
        ],
    ];

    private const array DAEMON_ERROR = [
        'errors' => [
            [
                'code' => 'DaemonConnectionException',
                'status' => '502',
                'detail' => 'There was an error while communicating with the machine running this server.',
            ],
        ],
    ];

    /**
     * Return the current resource utilization for a server. This value is cached for up to
     * 20 seconds at a time to ensure that repeated requests to this endpoint do not cause
     * a flood of unnecessary API calls.
     *
     *
     * @return ApiPayload
     *
     * @throws DaemonConnectionException
     */
    #[Endpoint('Get server resources', 'Returns the latest cached resource utilization reported by Wings for the server.')]
    #[ScribeResponse(self::STATS_EXAMPLE, description: 'Resource utilization returned.')]
    #[ScribeResponse(self::DAEMON_ERROR, status: 502, description: 'Wings could not be reached for resource utilization.')]
    #[ResponseField('attributes.current_state', 'string', example: 'running', enum: ['offline', 'stopped', 'starting', 'running', 'stopping'])]
    public function __invoke(GetServerRequest $request, ReadsServerState $state, Server $server): array
    {
        return Fractal::item($state->read($server))
            ->transformWith($this->getTransformer(StatsTransformer::class))
            ->toResponseArray();
    }
}
