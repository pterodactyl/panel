<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Models\Node;

class UpdateNodeRequest extends StoreNodeRequest
{
    /**
     * Apply validation rules to this request. Uses the parent class rules()
     * function but passes in the rules for updating rather than creating.
     */
    public function rules(array $rules = null): array
    {
        /** @var Node $node */
        $node = $this->route()->parameter('node');

        return parent::rules(Node::getRulesForUpdate($node->id));
    }
}
