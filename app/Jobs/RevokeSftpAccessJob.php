<?php

declare(strict_types=1);

namespace Pterodactyl\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\WithoutRelations;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Facades\Daemon;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;

/**
 * Revokes all SFTP access for a user on a given node or for a specific server.
 */
#[DeleteWhenMissingModels]
class RevokeSftpAccessJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $maxExceptions = 1;

    public int $timeout = 30;

    /** @var list<int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly string $user,
        #[WithoutRelations]
        public readonly Server|Node $target,
    ) {}

    public function uniqueId(): string
    {
        $target = $this->target instanceof Node ? "node:{$this->target->uuid}" : "server:{$this->target->uuid}";

        return "revoke-sftp:{$this->user}:{$target}";
    }

    public function handle(): void
    {
        $node = $this->target instanceof Node ? $this->target : $this->target->node;

        try {
            Daemon::node($node)->deauthorize(
                $this->user,
                $this->target instanceof Server ? [$this->target->uuid] : []
            );
        } catch (DaemonConnectionException) {
            // Keep retrying this job with a longer and longer backoff until we hit three
            // attempts at which point we stop and will assume the node is fully offline
            // and we are just wasting time.
            $this->release($this->attempts() * 10);
        }
    }
}
