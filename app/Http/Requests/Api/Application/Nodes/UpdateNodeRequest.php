<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Validation\NodeRules;

class UpdateNodeRequest extends StoreNodeRequest
{
    /**
     * Apply validation rules to this request. Uses the parent class rules()
     * function but passes in the rules for updating rather than creating.
     *
     * @param  NormalizedValidationRules|null  $rules
     * @return ValidationRules
     */
    public function rules(?array $rules = null): array
    {
        return [
            ...parent::rules(NodeRules::rules()),
            'reset_secret' => ['sometimes', 'boolean'],
        ];
    }
}
