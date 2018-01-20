<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\EggVariable;

class EggVariableTransformer extends BaseTransformer
{
    public function transform(EggVariable $model)
    {
        return $model->toArray();
    }
}
