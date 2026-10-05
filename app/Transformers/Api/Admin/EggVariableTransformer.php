<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\EggVariable;

#[ResponseField('sort_order', 'integer', example: 0, nullable: true)]
class EggVariableTransformer extends BaseAdminTransformer
{
    public function getResourceName(): string
    {
        return EggVariable::RESOURCE_NAME;
    }

    /**
     * @return ApiPayload
     */
    public function transform(EggVariable $model): array
    {
        return [
            'id' => $model->id,
            'egg_id' => $model->egg_id,
            'name' => $model->name,
            'description' => $model->description,
            'env_variable' => $model->env_variable,
            'default_value' => $model->default_value,
            'user_viewable' => $model->user_viewable,
            'user_editable' => $model->user_editable,
            'rules' => $model->rules,
            'sort_order' => $model->sort_order,
            'created_at' => $this->formatTimestamp($model->created_at),
            'updated_at' => $this->formatTimestamp($model->updated_at),
        ];
    }
}
