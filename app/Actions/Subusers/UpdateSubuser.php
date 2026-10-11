<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Subusers;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Subusers\UpdatesSubusers;
use Pterodactyl\Facades\Activity;
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
     * Permissions that are already the subuser's, in any order, change nothing.
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

        $current = $subuser->permissions;
        $requested = $permissions;
        sort($current);
        sort($requested);
        if ($requested === $current) {
            return $subuser;
        }

        DB::transaction(function () use ($server, $subuser, $permissions): void {
            $subuser->update(['permissions' => $permissions]);

            $job = new RevokeSftpAccessJob($subuser->user->uuid, $server);
            DB::afterCommit(fn (): PendingDispatch => dispatch($job));
        });

        Activity::event('server:subuser.update')
            ->subject($server, $subuser->user)
            ->property(['email' => $subuser->user->email, 'old' => $current, 'new' => $requested, 'revoked' => true])
            ->log();

        return $subuser;
    }
}
