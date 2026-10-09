<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Daemon;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Lcobucci\JWT\UnencryptedToken;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;

final readonly class ServerClient
{
    private function __construct(private string $uuid, private DaemonConnection $connection) {}

    public static function forServer(Server $server, ?Node $node = null): self
    {
        return new self($server->uuid, DaemonConnection::forNode($node ?? $server->node));
    }

    public function files(): FilesClient
    {
        return FilesClient::forServer($this->uuid, $this->connection);
    }

    public function backups(): BackupsClient
    {
        return BackupsClient::forServer($this->uuid, $this->connection);
    }

    /** @return DaemonStats */
    public function details(): array
    {
        $response = $this->connection->request('GET', "/api/servers/{$this->uuid}", useStatusCode: false);
        $data = $this->connection->decode($response, [
            'data.state' => ['sometimes', 'string'],
            'data.is_suspended' => ['sometimes', 'boolean:strict'],
            'data.utilization' => ['sometimes', 'array'],
            'data.utilization.memory_bytes' => ['sometimes', 'integer:strict'],
            'data.utilization.cpu_absolute' => ['sometimes', 'numeric:strict'],
            'data.utilization.disk_bytes' => ['sometimes', 'integer:strict'],
            'data.utilization.uptime' => ['sometimes', 'integer:strict'],
            'data.utilization.network' => ['sometimes', 'array'],
            'data.utilization.network.rx_bytes' => ['sometimes', 'integer:strict'],
            'data.utilization.network.tx_bytes' => ['sometimes', 'integer:strict'],
        ]);

        // SAFETY: the transport validates the bounded JSON and the stats shape above.
        /** @var DaemonStats $data */
        return $data;
    }

    public function create(bool $startOnCompletion = true): void
    {
        $this->connection->request('POST', '/api/servers', ['json' => [
            'uuid' => $this->uuid,
            'start_on_completion' => $startOnCompletion,
        ]]);
    }

    public function sync(): void
    {
        $this->connection->request('POST', "/api/servers/{$this->uuid}/sync");
    }

    public function delete(): void
    {
        $this->connection->request('DELETE', "/api/servers/{$this->uuid}");
    }

    public function reinstall(): void
    {
        $this->connection->request('POST', "/api/servers/{$this->uuid}/reinstall");
    }

    public function power(string $action): Response
    {
        return $this->connection->request('POST', "/api/servers/{$this->uuid}/power", ['json' => ['action' => $action]]);
    }

    /** @param list<string>|string $commands */
    public function commands(array|string $commands): Response
    {
        return $this->connection->request('POST', "/api/servers/{$this->uuid}/commands", [
            'json' => ['commands' => Arr::wrap($commands)],
        ]);
    }

    /**
     * Read the most recent console output lines. Wings never returns more
     * than its own ceiling of 100 lines, whatever size is requested.
     *
     * @return list<string>
     */
    public function logs(int $lines = 100): array
    {
        $response = $this->connection->request('GET', "/api/servers/{$this->uuid}/logs", [
            'query' => ['size' => $lines],
        ]);
        $data = $this->connection->decode($response, [
            'data.data' => ['present', 'nullable', 'array', 'list'],
            'data.data.*' => ['string'],
        ]);

        // SAFETY: the transport validates the bounded JSON and the list of lines above.
        /** @var array{data: list<string>|null} $data */
        return $data['data'] ?? [];
    }

    public function transfer(Node $target, UnencryptedToken $token): void
    {
        $this->connection->request('POST', "/api/servers/{$this->uuid}/transfer", ['json' => [
            'server_id' => $this->uuid,
            'url' => $target->getConnectionAddress().'/api/transfers',
            'token' => 'Bearer '.$token->toString(),
            'server' => ['uuid' => $this->uuid, 'start_on_completion' => false],
        ]]);
    }
}
