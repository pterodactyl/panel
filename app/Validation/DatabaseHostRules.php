<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

final class DatabaseHostRules
{
    /**
     * Validation rules for the database host requests.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'host' => ['required', 'string', 'regex:/^[\w\-\.]+$/'],
            'port' => ['required', 'numeric', 'between:1,65535'],
            'username' => ['required', 'string', 'max:32'],
            'password' => ['nullable', 'string'],
            'node_id' => ['sometimes', 'nullable', 'integer', 'exists:nodes,id'],
        ];
    }
}
