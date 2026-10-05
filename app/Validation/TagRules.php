<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

use Illuminate\Validation\Rule;
use Pterodactyl\Models\Tag;

final class TagRules
{
    /**
     * Canonical validation rules for client-submitted tag fields. Pass the tag being
     * edited so its own slug does not collide with the uniqueness check.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(?Tag $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'between:1,191'],
            'slug' => ['required', 'string', 'between:1,191', Rule::unique('tags', 'slug')->ignore($ignore)],
            // Custom tags store an optional chip colour; built-in tags ignore it - their
            // colour is owned by EggSpecificTags. Null clears it.
            'color' => ['nullable', 'string', 'hex_color'],
            'legacy_nest_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
