<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\NotIn;
use Pterodactyl\Models\Mount;

final class MountRules
{
    /**
     * Validation rules for the mount requests.
     *
     * @param  Mount|null  $ignore  the mount being updated
     * @return NormalizedValidationRules
     */
    public static function rules(?Mount $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:64', Rule::unique('mounts', 'name')->ignore($ignore)],
            'description' => ['nullable', 'string', 'max:191'],
            'source' => ['required', 'string', new NotIn(Mount::$invalidSourcePaths)],
            'target' => ['required', 'string', new NotIn(Mount::$invalidTargetPaths)],
            'read_only' => ['sometimes', 'boolean'],
            'user_mountable' => ['sometimes', 'boolean'],
        ];
    }
}
