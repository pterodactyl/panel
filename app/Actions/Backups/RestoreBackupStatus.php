<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Backups;

use Pterodactyl\Contracts\Backups\RestoresBackupStatuses;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Node;

final class RestoreBackupStatus implements RestoresBackupStatuses
{
    public function restoreStatus(string $backupUuid, Node $node): Backup
    {
        $model = Backup::query()->where('uuid', $backupUuid)->firstOrFail();

        throw_unless($model->server->node->is($node), HttpForbiddenException::class, 'Requesting node does not have permission to access this server.');

        $model->server->update(['status' => null]);

        return $model;
    }
}
