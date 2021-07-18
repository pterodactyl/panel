<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\WebauthnKey;

class WebauthnKeyTransformer extends BaseClientTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return 'webauthn_key';
    }

    /**
     * Return basic information about the currently logged in user.
     */
    public function transform(WebauthnKey $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'created_at' => $model->created_at->toIso8601String(),
            'last_used_at' => now()->toIso8601String(),
        ];
    }
}
