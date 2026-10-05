<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

final class DatabaseRules
{
    /**
     * Validation rules for database attributes, consumed by the requests that accept them.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'server_id' => ['required', 'numeric', 'exists:servers,id'],
            'database_host_id' => ['required', 'exists:database_hosts,id'],
            'database' => ['required', 'string', 'alpha_dash', 'between:3,48'],
            'username' => ['string', 'alpha_dash', 'between:3,100'],
            'max_connections' => ['nullable', 'integer'],
            'remote' => ['required', 'string', 'regex:/^[\w\-\/.%:]+$/'],
            'password' => ['string'],
        ];
    }
}
