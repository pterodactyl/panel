<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\ApiKey;

/**
 * @extends ClientResource<ApiKey>
 */
#[ResponseField('allowed_ips', schema: ['type' => 'array', 'nullable' => true, 'items' => ['type' => 'string'], 'example' => ['127.0.0.1']])]
class ApiKeyResource extends ClientResource
{
    public function resourceName(): string
    {
        return ApiKey::RESOURCE_NAME;
    }

    public function toArray(Request $request): array
    {
        return [
            'identifier' => $this->resource->identifier,
            'description' => $this->resource->memo,
            'allowed_ips' => $this->resource->allowed_ips,
            'last_used_at' => $this->resource->last_used_at ? $this->resource->last_used_at->toAtomString() : null,
            'created_at' => $this->formatTimestamp($this->resource->created_at),
        ];
    }
}
