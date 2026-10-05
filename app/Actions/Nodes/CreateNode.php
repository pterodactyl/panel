<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Nodes;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Nodes\CreatesNodes;
use Pterodactyl\Models\Node;

final readonly class CreateNode implements CreatesNodes
{
    /**
     * Create a node and mint its daemon token pair.
     *
     * @param  NodeCreationData  $data
     */
    public function create(array $data): Node
    {
        $node = Node::query()->forceCreate([
            ...$data,
            'daemon_token' => Crypt::encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH)),
            'daemon_token_id' => Str::random(Node::DAEMON_TOKEN_ID_LENGTH),
        ]);

        return $node->refresh();
    }
}
