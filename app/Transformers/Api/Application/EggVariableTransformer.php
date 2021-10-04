<?php

namespace Pterodactyl\Transformers\Api\Application;

use Pterodactyl\Models\EggVariable;
use Pterodactyl\Transformers\Api\Transformer;

class EggVariableTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return EggVariable::RESOURCE_NAME;
    }

    public function transform(EggVariable $model): array
    {
        return $model->toArray();
    }
}
