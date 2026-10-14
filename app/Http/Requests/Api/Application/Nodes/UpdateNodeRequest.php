<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Models\Node;

class UpdateNodeRequest extends StoreNodeRequest
{
    /**
     * Apply validation rules to this request: a node's fields, and whether to rotate its
     * daemon secret.
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
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Node
    {
        return $this->parameter('node', Node::class);
    }
}
