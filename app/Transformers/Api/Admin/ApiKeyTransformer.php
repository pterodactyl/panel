<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Admin;

use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use UnexpectedValueException;

#[ResponseField('created_by', schema: ['type' => 'object', 'required' => ['id', 'username', 'email'], 'properties' => ['id' => ['type' => 'integer', 'example' => 1], 'username' => ['type' => 'string', 'example' => 'admin'], 'email' => ['type' => 'string', 'example' => 'admin@example.com']]])]
class ApiKeyTransformer extends BaseAdminTransformer
{
    public function getResourceName(): string
    {
        return ApiKey::RESOURCE_NAME;
    }

    /** Never exposes the token; the plaintext secret is only returned once on create. */
    /**
     * @return ApiPayload
     */
    public function transform(ApiKey $model): array
    {
        $permissions = [];
        foreach (AdminAcl::getResourceList() as $resource) {
            $column = AdminAcl::COLUMN_IDENTIFIER.$resource;
            $permission = $model->getAttribute($column);
            if ($permission === null) {
                $permission = AdminAcl::NONE;
            } elseif (! is_int($permission)) {
                throw new UnexpectedValueException("The API key permission [{$column}] must be an integer.");
            }

            $permissions[$column] = $permission;
        }

        return array_merge([
            'identifier' => $model->identifier,
            'memo' => $model->memo,
            'created_by' => [
                'id' => $model->user->id,
                'username' => $model->user->username,
                'email' => $model->user->email,
            ],
            'last_used_at' => $model->last_used_at ? $this->formatTimestamp($model->last_used_at) : null,
            'created_at' => $this->formatTimestamp($model->created_at),
            'relationships' => [],
        ], $permissions);
    }
}
