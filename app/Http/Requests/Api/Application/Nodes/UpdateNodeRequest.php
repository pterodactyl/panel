<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Models\Node;

class UpdateNodeRequest extends StoreNodeRequest
{
    /**
     * Determine if the node being requested for editing exists
     * on the Panel before validating the data.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $node = $this->route()->parameter('node');

        return $node instanceof Node && $node->exists;
    }

    /**
     * Apply validation rules to this request. Uses the parent class rules()
     * function but passes in the rules for updating rather than creating.
     *
     * @param array|null $rules
     * @return array
     */
    public function rules(array $rules = null): array
    {
        $nodeId = $this->route()->parameter('node')->id;

        return parent::rules(Node::getUpdateRulesForId($nodeId));
    }
}
