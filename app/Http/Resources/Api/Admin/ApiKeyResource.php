<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Admin;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use UnexpectedValueException;

/**
 * @extends AdminResource<ApiKey>
 */
#[ResponseField('created_by', schema: ['type' => 'object', 'required' => ['id', 'username', 'email'], 'properties' => ['id' => ['type' => 'integer', 'example' => 1], 'username' => ['type' => 'string', 'example' => 'admin'], 'email' => ['type' => 'string', 'example' => 'admin@example.com']]])]
class ApiKeyResource extends AdminResource
{
    public function resourceName(): string
    {
        return ApiKey::RESOURCE_NAME;
    }

    /**
     * Never exposes the token; the plaintext secret is only returned once on create.
     */
    public function toArray(Request $request): array
    {
        $permissions = [];
        foreach (AdminAcl::getResourceList() as $resource) {
            $column = AdminAcl::COLUMN_IDENTIFIER.$resource;
            $permission = $this->resource->getAttribute($column);
            if ($permission === null) {
                $permission = AdminAcl::NONE;
            } elseif (! is_int($permission)) {
                throw new UnexpectedValueException("The API key permission [{$column}] must be an integer.");
            }

            $permissions[$column] = $permission;
        }

        return array_merge([
            'identifier' => $this->resource->identifier,
            'memo' => $this->resource->memo,
            'created_by' => [
                'id' => $this->resource->user->id,
                'username' => $this->resource->user->username,
                'email' => $this->resource->user->email,
            ],
            'last_used_at' => $this->resource->last_used_at ? $this->formatTimestamp($this->resource->last_used_at) : null,
            'created_at' => $this->formatTimestamp($this->resource->created_at),
            'relationships' => [],
        ], $permissions);
    }
}
