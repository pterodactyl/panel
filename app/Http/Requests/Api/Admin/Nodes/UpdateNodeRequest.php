<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Nodes;

use Closure;
use Illuminate\Validation\Validator;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Validation\NodeRules;

class UpdateNodeRequest extends StoreNodeRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminNodesUpdate];
    }

    /**
     * Validation rules for updating a node.
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

    /**
     * Existing HTTP nodes can still be updated when the Panel is served over HTTPS.
     *
     * @return list<Closure(Validator): void>
     */
    public function after(): array
    {
        return [];
    }
}
