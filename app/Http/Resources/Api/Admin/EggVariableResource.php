<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\EggVariable;

/**
 * @extends AdminResource<EggVariable>
 */
#[ResponseField('sort_order', 'integer', example: 0, nullable: true)]
class EggVariableResource extends AdminResource
{
    public function resourceName(): string
    {
        return EggVariable::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'egg_id' => $this->resource->egg_id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'env_variable' => $this->resource->env_variable,
            'default_value' => $this->resource->default_value,
            'user_viewable' => $this->resource->user_viewable,
            'user_editable' => $this->resource->user_editable,
            'rules' => $this->resource->rules,
            'sort_order' => $this->resource->sort_order,
            'created_at' => $this->formatTimestamp($this->resource->created_at),
            'updated_at' => $this->formatTimestamp($this->resource->updated_at),
        ];
    }
}
