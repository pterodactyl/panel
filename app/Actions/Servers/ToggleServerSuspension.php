<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Servers;

use Exception;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Pterodactyl\Contracts\Servers\TogglesServerSuspension;
use Pterodactyl\Events\Server\OperationCompleted;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Server;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final readonly class ToggleServerSuspension implements TogglesServerSuspension
{
    public const string ACTION_SUSPEND = TogglesServerSuspension::ACTION_SUSPEND;

    public const string ACTION_UNSUSPEND = TogglesServerSuspension::ACTION_UNSUSPEND;

    /** @throws Throwable */
    public function toggle(Server $server, string $action = self::ACTION_SUSPEND): void
    {
        throw_unless(in_array($action, [self::ACTION_SUSPEND, self::ACTION_UNSUSPEND], true), InvalidArgumentException::class, "Unsupported suspension action [$action].");
        $isSuspending = $action === self::ACTION_SUSPEND;
        // Repeating the current state does not require another Wings sync.
        if ($isSuspending === $server->isSuspended()) {
            return;
        }

        // A transfer can race with Wings state synchronization, so reject it first.
        throw_if($server->transfer !== null, ConflictHttpException::class, 'Cannot toggle suspension status on a server that is currently being transferred.');
        $server->update(['status' => $isSuspending ? Server::STATUS_SUSPENDED : null]);

        try {
            Daemon::server($server)->sync();
        } catch (Exception $exception) {
            // Restore the Panel state if Wings could not accept the update.
            $server->update(['status' => $isSuspending ? null : Server::STATUS_SUSPENDED]);
            throw $exception;
        }

        // Only a state change Wings accepted is reported; repeats and reverted attempts are not.
        Event::dispatch(new OperationCompleted($server->uuid, $isSuspending ? 'suspend' : 'unsuspend', true, $server->uuid));
    }
}
