<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\ApiKey;

#[ResponseField('allowed_ips', schema: ['type' => 'array', 'nullable' => true, 'items' => ['type' => 'string'], 'example' => ['127.0.0.1']])]
class ApiKeyTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return ApiKey::RESOURCE_NAME;
    }

    /**
     * Transform this model into a representation that can be consumed by a client.
     *
     * @return ApiPayload
     */
    public function transform(ApiKey $model): array
    {
        return [
            'identifier' => $model->identifier,
            'description' => $model->memo,
            'allowed_ips' => $model->allowed_ips,
            'last_used_at' => $model->last_used_at ? $model->last_used_at->toAtomString() : null,
            'created_at' => $this->formatTimestamp($model->created_at),
        ];
    }
}
