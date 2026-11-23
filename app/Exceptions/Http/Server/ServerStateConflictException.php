<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Http\Server;

use Pterodactyl\Models\Server;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class ServerStateConflictException extends ConflictHttpException
{
    /**
     * Exception thrown when the server is in an unsupported state for API access or
     * certain operations within the codebase.
     */
    public function __construct(Server $server, ?Throwable $previous = null)
    {
        $message = __('exceptions.server.unsupported_state');
        if ($server->isSuspended()) {
            $message = __('exceptions.server.suspended');
        } elseif ($server->node->isUnderMaintenance()) {
            $message = __('exceptions.server.node_maintenance');
        } elseif (! $server->isInstalled()) {
            $message = __('exceptions.server.not_installed');
        } elseif ($server->status === Server::STATUS_RESTORING_BACKUP) {
            $message = __('exceptions.server.restoring_backup');
        } elseif (($server->transfer) !== null) {
            $message = __('exceptions.server.transferring');
        }

        parent::__construct($message, $previous);
    }
}
