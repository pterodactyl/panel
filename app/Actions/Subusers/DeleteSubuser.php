<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Subusers;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Subusers\DeletesSubusers;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Throwable;

final readonly class DeleteSubuser implements DeletesSubusers
{
    /**
     * Removes the subuser from the server and revokes their outstanding SFTP sessions on Wings.
     *
     * @throws Throwable
     */
    public function delete(Server $server, Subuser $subuser): void
    {
        DB::transaction(function () use ($server, $subuser): void {
            $subuser->delete();

            $job = new RevokeSftpAccessJob($subuser->user->uuid, $server);
            DB::afterCommit(fn (): PendingDispatch => dispatch($job));
        });
    }
}
