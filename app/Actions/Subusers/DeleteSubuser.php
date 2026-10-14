<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Subusers;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Subusers\DeletesSubusers;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;

final readonly class DeleteSubuser implements DeletesSubusers
{
    public function delete(Server $server, Subuser $subuser): void
    {
        DB::transaction(function () use ($server, $subuser): void {
            $subuser->delete();

            $job = new RevokeSftpAccessJob($subuser->user->uuid, $server);
            DB::afterCommit(fn (): PendingDispatch => dispatch($job));
        });

        Activity::event('server:subuser.delete')
            ->subject($server, $subuser->user)
            ->property(['email' => $subuser->user->email, 'revoked' => true])
            ->log();
    }
}
