<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Subusers;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Subusers\UpdatesSubusers;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Services\Servers\SubuserPermissionCatalog;
use Throwable;

final readonly class UpdateSubuser implements UpdatesSubusers
{
    public function __construct(private SubuserPermissionCatalog $catalog) {}

    /**
     * Replaces the subuser's permissions and revokes their outstanding SFTP sessions on Wings.
     *
     * @param  list<string>  $permissions
     *
     * @throws Throwable
     */
    public function update(Server $server, Subuser $subuser, array $permissions): Subuser
    {
        // A permission the subuser already holds stays grantable even once nothing defines
        // it, such as one of a disabled extension, so a caller can hand it back unchanged.
        $permissions = CreateSubuser::permissions($permissions, [...$this->catalog->keys(), ...$subuser->permissions]);

        return DB::transaction(function () use ($server, $subuser, $permissions): Subuser {
            $subuser->update(['permissions' => $permissions]);

            $job = new RevokeSftpAccessJob($subuser->user->uuid, $server);
            DB::afterCommit(fn (): PendingDispatch => dispatch($job));

            return $subuser;
        });
    }
}
