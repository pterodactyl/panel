<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Backups;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;

class BackupListService
{
    /**
     * @return array{backups: LengthAwarePaginator<int, Backup>, backup_count: int}
     */
    public function handle(Server $server, int $perPage): array
    {
        return [
            'backups' => $server->backups()->paginate($perPage),
            'backup_count' => $server->backups()->nonFailed()->count(),
        ];
    }
}
