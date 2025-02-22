<?php

namespace Pterodactyl\Exceptions\Http\Server;

use Pterodactyl\Models\Server;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ServerStateConflictException extends ConflictHttpException
{
    /**
     * Exception thrown when the server is in an unsupported state for API access or
     * certain operations within the codebase.
     */
    public function __construct(Server $server, \Throwable $previous = null)
    {
        $message = 'This server is currently in an unsupported state, please try again later.';
        if ($server->isSuspended()) {
            $message = 'This server is currently suspended and the functionality requested is unavailable.';
        } elseif ($server->node->isUnderMaintenance()) {
            $message = 'The node of this server is currently under maintenance and the functionality requested is unavailable.';
        } elseif (!$server->isInstalled()) {
            $message = 'This server has not yet completed its installation process, please try again later.';
        } elseif ($server->status === Server::STATUS_RESTORING_BACKUP) {
            $message = 'This server is currently restoring from a backup, please try again later.';
        } elseif (!is_null($server->transfer)) {
            $message = 'This server is currently being transferred to a new machine, please try again later.';
        }

        parent::__construct($message, $previous);
    }
}
