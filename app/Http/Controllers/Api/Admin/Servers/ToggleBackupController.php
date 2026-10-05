<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Controllers\Api\Admin\Servers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseFromTransformer;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Facades\Fractal;
use Pterodactyl\Http\Controllers\Api\Admin\AdminApiController;
use Pterodactyl\Http\Requests\Api\Admin\Servers\Backups\ToggleBackupRequest;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Server;
use Pterodactyl\Transformers\Api\Admin\BackupTransformer;

#[Group('Admin API', 'Root administrator endpoints for managing panel configuration and resources.')]
#[Subgroup('Server Backups', 'Inspect and lock backups assigned to a server.')]
class ToggleBackupController extends AdminApiController
{
    /**
     * Toggle backup lock state.
     *
     * @return ApiPayload
     */
    #[Endpoint('Toggle server backup lock', 'Toggles whether a server backup is locked against deletion.')]
    #[ResponseFromTransformer(BackupTransformer::class, Backup::class, description: 'Server backup lock state toggled.', resourceKey: 'backup')]
    public function __invoke(ToggleBackupRequest $request, Server $server, Backup $backup): array
    {
        if ($backup->server_id !== $server->id) {
            throw (new ModelNotFoundException)->setModel(Backup::class);
        }

        $backup->update(['is_locked' => ! $backup->is_locked]);

        Activity::event('admin:server-backup.toggle-lock')
            ->subject($backup)
            ->property(['name' => $backup->name, 'locked' => $backup->is_locked])
            ->log();

        return Fractal::item($backup)
            ->transformWith($this->getTransformer(BackupTransformer::class))
            ->toResponseArray();
    }
}
