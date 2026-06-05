<?php

namespace Pterodactyl\Listeners;

use Pterodactyl\Models\Node;
use Pterodactyl\Events\User\Deleting;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection;
use Pterodactyl\Events\User\PasswordChanged;
use Pterodactyl\Extensions\Illuminate\Events\Contracts\SubscribesToEvents;

class RevocationListener implements SubscribesToEvents
{
    public function revoke(Deleting|PasswordChanged $event): void
    {
        $user = $event->user;

        // Look at all of the nodes that a user is associated with and trigger a job
        // that disconnects them from websockets and SFTP.
        Node::query()
            ->whereIn('nodes.id', $user->accessibleServers()->select('servers.node_id')->distinct())
            ->chunk(50, function (Collection $nodes) use ($user) {
                $nodes->each(fn (Node $node) => RevokeSftpAccessJob::dispatch($user->uuid, $node));
            });
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Deleting::class, [self::class, 'revoke']);
        $events->listen(PasswordChanged::class, [self::class, 'revoke']);
    }
}
