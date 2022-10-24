<?php

namespace Pterodactyl\Services\Nodes;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Pterodactyl\Models\Node;
use Illuminate\Contracts\Encryption\Encrypter;

class NodeCreationService
{
    /**
     * NodeCreationService constructor.
     */
    public function __construct(private Encrypter $encrypter)
    {
    }

    /**
     * Create a new node on the panel.
     *
     */
    public function handle(array $data): Node
    {
        $data['uuid'] = Uuid::uuid4()->toString();
        $data['daemon_token'] = $this->encrypter->encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH));
        $data['daemon_token_id'] = Str::random(Node::DAEMON_TOKEN_ID_LENGTH);

        /** @var Node $node */
        $node = Node::query()->create($data);

        return $node;
    }
}
