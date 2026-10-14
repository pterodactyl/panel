<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Nodes;

use Closure;
use Illuminate\Validation\Validator;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Node;

class UpdateNodeRequest extends StoreNodeRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminNodesUpdate];
    }

    /**
     * Validation rules for updating a node.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
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

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Node
    {
        return $this->parameter('node', Node::class);
    }
}
