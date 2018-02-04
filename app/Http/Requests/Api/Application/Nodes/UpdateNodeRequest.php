<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Models\Node;

class UpdateNodeRequest extends StoreNodeRequest
{
    /**
     * Apply validation rules to this request. Uses the parent class rules()
     * function but passes in the rules for updating rather than creating.
     *
     * @param array|null $rules
     * @return array
     */
    public function rules(array $rules = null): array
    {
        $nodeId = $this->getModel(Node::class)->id;

        return parent::rules(Node::getUpdateRulesForId($nodeId));
    }
}
