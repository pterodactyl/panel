<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Nodes;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Nodes\ResetsServerStates;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\Backup;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;

final readonly class ResetServerStates implements ResetsServerStates
{
    public function reset(Node $node): void
    {
        $servers = Server::query()
            ->with([
                'activity' => function (Relation $relation): void {
                    $relation->getQuery()
                        ->with('subjects.subject')
                        ->where('activity_logs.event', 'server:backup.restore')
                        ->latest('timestamp');
                },
            ])
            ->where('node_id', $node->id)
            ->where('status', Server::STATUS_RESTORING_BACKUP)
            ->get();

        DB::transaction(function () use ($node, $servers): void {
            foreach ($servers as $server) {
                $subject = $server->activity->first()?->subjects->where('subject_type', 'backup')->first();
                if ($subject !== null && $subject->subject instanceof Backup) {
                    Activity::event('server:backup.restore-failed')
                        ->subject($server, $subject->subject)
                        ->property('name', $subject->subject->name)
                        ->log();
                }
            }

            Server::query()->where('node_id', $node->id)
                ->whereIn('status', [Server::STATUS_INSTALLING, Server::STATUS_RESTORING_BACKUP])
                ->update(['status' => null]);
        });
    }
}
