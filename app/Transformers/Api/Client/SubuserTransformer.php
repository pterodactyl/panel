<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Transformers\Api\Transformer;

class SubuserTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    public function transform(Subuser $model): array
    {
        return array_merge(
            (new UserTransformer())->transform($model->user),
            ['permissions' => $model->permissions]
        );
    }
}
