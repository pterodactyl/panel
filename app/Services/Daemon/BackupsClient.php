<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Daemon;

use Illuminate\Http\Client\Response;
use Pterodactyl\Models\Backup;

final readonly class BackupsClient
{
    private function __construct(private string $uuid, private DaemonConnection $connection) {}

    public static function forServer(string $uuid, DaemonConnection $connection): self
    {
        return new self($uuid, $connection);
    }

    public function create(Backup $backup, ?string $adapter = null): Response
    {
        return $this->connection->request('POST', "/api/servers/{$this->uuid}/backup", ['json' => [
            'adapter' => $adapter ?? $backup->disk,
            'uuid' => $backup->uuid,
            'ignore' => implode("\n", $backup->ignored_files),
        ]]);
    }

    public function restore(Backup $backup, ?string $url = null, bool $truncate = false): Response
    {
        return $this->connection->request('POST', "/api/servers/{$this->uuid}/backup/{$backup->uuid}/restore", ['json' => [
            'adapter' => $backup->disk,
            'truncate_directory' => $truncate,
            'download_url' => $url ?? '',
        ]]);
    }

    public function delete(Backup $backup): Response
    {
        return $this->connection->request('DELETE', "/api/servers/{$this->uuid}/backup/{$backup->uuid}");
    }
}
