<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

use Pterodactyl\Services\Acl\Api\AdminAcl;

final class ApiKeyRules
{
    /**
     * Validation rules shared by the admin and client API key requests.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'key_type' => ['present', 'integer', 'min:0', 'max:4'],
            'identifier' => ['required', 'string', 'size:16', 'unique:api_keys,identifier'],
            'token' => ['required', 'string'],
            'memo' => ['required', 'nullable', 'string', 'max:500'],
            'allowed_ips' => ['nullable', 'array'],
            'allowed_ips.*' => ['string'],
            'last_used_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'r_'.AdminAcl::RESOURCE_USERS => ['integer', 'min:0', 'max:3'],
            'r_'.AdminAcl::RESOURCE_ALLOCATIONS => ['integer', 'min:0', 'max:3'],
            'r_'.AdminAcl::RESOURCE_DATABASE_HOSTS => ['integer', 'min:0', 'max:3'],
            'r_'.AdminAcl::RESOURCE_SERVER_DATABASES => ['integer', 'min:0', 'max:3'],
            'r_'.AdminAcl::RESOURCE_EGGS => ['integer', 'min:0', 'max:3'],
            'r_'.AdminAcl::RESOURCE_LOCATIONS => ['integer', 'min:0', 'max:3'],
            'r_'.AdminAcl::RESOURCE_NODES => ['integer', 'min:0', 'max:3'],
            'r_'.AdminAcl::RESOURCE_SERVERS => ['integer', 'min:0', 'max:3'],
        ];
    }
}
