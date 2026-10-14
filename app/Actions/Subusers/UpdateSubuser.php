<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Subusers;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Subusers\UpdatesSubusers;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Services\Servers\SubuserPermissionCatalog;

final readonly class UpdateSubuser implements UpdatesSubusers
{
    public function __construct(private SubuserPermissionCatalog $catalog) {}

    public function update(Server $server, Subuser $subuser, array $permissions): Subuser
    {
        // A permission the subuser already holds stays grantable even once nothing defines
        // it, such as one of a disabled extension, so a caller can hand it back unchanged.
        $rules = CreateSubuser::rules([...$this->catalog->keys(), ...$subuser->permissions]);
        Validator::validate(['permissions' => $permissions], Arr::only($rules, ['permissions', 'permissions.*']));

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
