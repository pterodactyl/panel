<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Nodes;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Nodes\CreatesNodes;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Extensions\ExtensionFields;

final readonly class CreateNode implements CreatesNodes
{
    public function __construct(private ExtensionFields $extensions) {}

    /**
     * Create a node and mint its daemon token pair.
     *
     * @param  NodeCreationData  $data
     */
    public function create(array $data): Node
    {
        $extensions = $data['extensions'] ?? [];
        unset($data['extensions']);

        return DB::transaction(function () use ($data, $extensions): Node {
            $node = Node::query()->forceCreate([
                ...$data,
                'daemon_token' => Crypt::encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH)),
                'daemon_token_id' => Str::random(Node::DAEMON_TOKEN_ID_LENGTH),
            ]);
            $this->extensions->save($node, $extensions);

            return $node->refresh();
        });
    }
}
