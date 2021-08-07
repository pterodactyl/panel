<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Transformers\Api\Transformer;

class EggVariableTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return Egg::RESOURCE_NAME;
    }

    public function transform(EggVariable $model): array
    {
        return $model->toArray();
    }
}
