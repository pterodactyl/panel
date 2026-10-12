<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Contracts\Backups\TogglesBackupLocks;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromResource;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\Backups\ToggleBackupRequest;
use Pterodactyl\Http\Resources\Api\Admin\BackupResource;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Server Backups', 'Inspect and lock backups assigned to a server.')]
class ToggleBackupController extends AdminApiController
{
    /**
     * Toggle backup lock state.
     */
    #[Endpoint('Toggle server backup lock', 'Toggles whether a server backup is locked against deletion.')]
    #[ResponseFromResource(BackupResource::class, Backup::class, description: 'Server backup lock state toggled.', resourceKey: 'backup')]
    public function __invoke(ToggleBackupRequest $request, TogglesBackupLocks $toggle, Server $server, Backup $backup): BackupResource
    {
        if ($backup->server_id !== $server->id) {
            throw (new ModelNotFoundException)->setModel(Backup::class);
        }

        $backup = $toggle->toggle($backup);

        Activity::event('admin:server-backup.toggle-lock')
            ->subject($backup)
            ->property(['name' => $backup->name, 'locked' => $backup->is_locked])
            ->log();

        return BackupResource::make($backup);
    }
}
