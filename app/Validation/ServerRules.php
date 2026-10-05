<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

use Illuminate\Validation\Rule;
use Pterodactyl\Models\Server;

final class ServerRules
{
    /**
     * Validation rules for the server requests.
     *
     * @param  Server|null  $ignore  the server being updated
     * @return NormalizedValidationRules
     */
    public static function rules(?Server $ignore = null): array
    {
        return [
            'external_id' => ['sometimes', 'nullable', 'string', 'between:1,191', Rule::unique('servers', 'external_id')->ignore($ignore)],
            'owner_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'min:1', 'max:191'],
            'node_id' => ['required', 'exists:nodes,id'],
            'description' => ['string'],
            'status' => ['nullable', 'string'],
            'memory' => ['required', 'numeric', 'min:0'],
            'swap' => ['required', 'numeric', 'min:-1'],
            'io' => ['required', 'numeric', 'between:10,1000'],
            'cpu' => ['required', 'numeric', 'min:0'],
            'threads' => ['nullable', 'regex:/^[0-9-,]+$/'],
            'oom_disabled' => ['sometimes', 'boolean'],
            'disk' => ['required', 'numeric', 'min:0'],
            'allocation_id' => ['required', 'bail', Rule::unique('servers', 'allocation_id')->ignore($ignore), 'exists:allocations,id'],
            'egg_id' => ['required', 'exists:eggs,id'],
            'startup' => ['required', 'string'],
            'skip_scripts' => ['sometimes', 'boolean'],
            'image' => ['required', 'string', 'max:191', 'regex:/^~?[\w\.\/\-:@ ]*$/'],
            'database_limit' => ['present', 'nullable', 'integer', 'min:0'],
            'allocation_limit' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'backup_limit' => ['present', 'nullable', 'integer', 'min:0'],
        ];
    }
}
