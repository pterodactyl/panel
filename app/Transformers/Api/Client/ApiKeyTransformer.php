<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\ApiKey;

class ApiKeyTransformer extends BaseClientTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getResourceName(): string
    {
        return ApiKey::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     */
    public function transform(ApiKey $model): array
    {
        return [
            'identifier' => $model->identifier,
            'description' => $model->memo,
            'allowed_ips' => $model->allowed_ips,
            'last_used_at' => $model->last_used_at ? $model->last_used_at->toAtomString() : null,
            'created_at' => $model->created_at->toAtomString(),
        ];
    }
}
