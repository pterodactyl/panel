<?php

declare(strict_types=1);

namespace Pterodactyl\Events\Server;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Pterodactyl\Events\Event;

/**
 * Immutable operation result; listeners never depend on a model surviving in the queue.
 * Only identifiers travel: after a `delete` the server row is already gone, so listeners
 * that need more must have stored it under the server uuid beforehand. `resourceUuid` is
 * the backup uuid for `backup` and the server uuid for every other operation.
 */
final class OperationCompleted extends Event implements ShouldDispatchAfterCommit
{
    /** @param 'provision'|'install'|'reinstall'|'backup'|'delete'|'suspend'|'unsuspend'|'transfer' $operation */
    public function __construct(
        public readonly string $serverUuid,
        public readonly string $operation,
        public readonly bool $successful,
        public readonly string $resourceUuid,
    ) {}
}
