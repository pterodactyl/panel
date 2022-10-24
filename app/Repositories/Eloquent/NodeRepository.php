<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Node;

class NodeRepository
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return Node::class;
    }
}
