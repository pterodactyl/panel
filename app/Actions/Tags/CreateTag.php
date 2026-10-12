<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Tags;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pterodactyl\Contracts\Tags\CreatesTags;
use Pterodactyl\Models\Tag;
use Pterodactyl\Rules\TagSlug;

final readonly class CreateTag implements CreatesTags
{
    /**
     * A tag's fields. Pass the tag being edited so its own slug does not collide with the
     * uniqueness check. A slug may not be purely numeric, so a leftover legacy nest id can be
     * told apart from a real tag and a slug can never shadow a tag id (see TagSlug).
     *
     * @return NormalizedValidationRules
     */
    public static function rules(?Tag $tag = null): array
    {
        return [
            'name' => ['required', 'string', 'between:1,191'],
            'slug' => ['required', 'string', 'between:1,191', Rule::unique('tags', 'slug')->ignore($tag), new TagSlug],
            // Custom tags store an optional chip colour; built-in tags ignore it - their
            // colour is owned by EggSpecificTags. Null clears it.
            'color' => ['nullable', 'string', 'hex_color'],
            'legacy_nest_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): Tag
    {
        $validated = Validator::validate($input, self::rules());
        // Blank strings are null, as they are in a request.
        $validated = array_map(fn (mixed $value): mixed => is_string($value) && blank($value) ? null : $value, $validated);

        return Tag::query()->create($validated);
    }
}
