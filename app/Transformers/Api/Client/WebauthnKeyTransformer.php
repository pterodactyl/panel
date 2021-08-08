<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\HardwareSecurityKey;
use Pterodactyl\Transformers\Api\Transformer;

class WebauthnKeyTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return HardwareSecurityKey::RESOURCE_NAME;
    }

    /**
     * Return basic information about the currently logged in user.
     */
    public function transform(HardwareSecurityKey $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'created_at' => self::formatTimestamp($model->created_at),
        ];
    }
}
