<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\ApiKeys;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Validation\ApiKeyRules;
use UnexpectedValueException;

class StoreApiKeyRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminApiKeysCreate];
    }

    /**
     * Validation rules for creating an application API key.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $modelRules = ApiKeyRules::rules();
        $rules = ['memo' => $modelRules['memo']];
        foreach (AdminAcl::getResourceList() as $resource) {
            $column = AdminAcl::COLUMN_IDENTIFIER.$resource;
            $rules[$column] = $modelRules[$column];
        }

        return $rules;
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'memo' => 'Description',
        ];
    }

    /**
     * The per-resource permission values from the validated payload.
     *
     * @return array<string, int>
     */
    public function getKeyPermissions(): array
    {
        $permissions = [];
        foreach ($this->validated() as $key => $value) {
            if (! str_starts_with($key, AdminAcl::COLUMN_IDENTIFIER)) {
                continue;
            }

            throw_unless(is_int($value), UnexpectedValueException::class, 'Validated API key permissions must be integers.');

            $permissions[$key] = $value;
        }

        return $permissions;
    }
}
