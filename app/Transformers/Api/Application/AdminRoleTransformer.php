<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\AdminRole;
use Pterodactyl\Transformers\Api\Transformer;

class AdminRoleTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return AdminRole::RESOURCE_NAME;
    }

    public function transform(AdminRole $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'description' => $model->description,
        ];
    }
}
