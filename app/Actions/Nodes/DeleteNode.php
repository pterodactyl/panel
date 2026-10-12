<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Nodes;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Nodes\DeletesNodes;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Models\Node;

final class DeleteNode implements DeletesNodes
{
    public function delete(Node $node): void
    {
        if ($node->servers()->exists()) {
            throw new HasActiveServersException(trans('exceptions.node.servers_attached'));
        }

        DB::transaction(function () use ($node): void {
            // Tag attachments are polymorphic, so no foreign key removes them with the node.
            $node->eggTags()->detach();
            $node->deploymentTags()->detach();
            $node->delete();
        });
    }
}
