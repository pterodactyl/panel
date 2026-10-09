<?php

declare(strict_types=1);

namespace Pterodactyl\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Pterodactyl\Events\User\Deleting;
use Pterodactyl\Events\User\PasswordChanged;
use Pterodactyl\Extensions\Illuminate\Events\Contracts\SubscribesToEvents;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Node;
use Pterodactyl\Support\JsonValueGuard;

class RevocationListener implements SubscribesToEvents
{
    public function revoke(Deleting|PasswordChanged $event): void
    {
        $user = $event->user;

        $user->forceFill([
            $user->getRememberTokenName() => Str::random(60),
        ])->saveQuietly();

        if (config('session.driver') === 'database') {
            $table = JsonValueGuard::string(config('session.table', 'sessions'));
            if ($table !== '') {
                $currentSessionId = session()->isStarted() ? session()->getId() : null;

                DB::table($table)
                    ->where('user_id', $user->id)
                    ->when($event instanceof PasswordChanged && $currentSessionId, function (Builder $query) use ($currentSessionId): void {
                        $query->where('id', '!=', $currentSessionId);
                    })
                    ->delete();
            }
        }

        // Look at all of the nodes that a user is associated with and trigger a job
        // that disconnects them from websockets and SFTP.
        Node::query()
            ->whereIn('nodes.id', $user->accessibleServers()->select('servers.node_id')->distinct())
            ->chunk(50, function (Collection $nodes) use ($user): void {
                $nodes->each(fn (Node $node): PendingDispatch => dispatch(new RevokeSftpAccessJob($user->uuid, $node)));
            });
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Deleting::class, self::revoke(...));
        $events->listen(PasswordChanged::class, self::revoke(...));
    }
}
