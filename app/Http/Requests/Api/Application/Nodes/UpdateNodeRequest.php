<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Models\Node;

class UpdateNodeRequest extends StoreNodeRequest
{
    /**
     * Apply validation rules to this request. Uses the parent class rules()
     * function but passes in the rules for updating rather than creating.
     */
    public function rules(?array $rules = null): array
    {
        $node = $this->parameter('node', Node::class);

        return parent::rules(Node::getRulesForUpdate($node));
    }
}
