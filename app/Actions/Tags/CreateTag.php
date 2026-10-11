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
     * Validates tag input and returns the model attributes it describes. The colour and legacy
     * nest id are left out when they are not given, so an edited tag keeps its stored values;
     * a blank colour is cleared.
     *
     * @param  ActionInput  $input
     * @return array{name: string, slug: string, color?: string|null, legacy_nest_id?: int|null}
     */
    public static function attributes(array $input, ?Tag $tag = null): array
    {
        $input = Validator::make($input, self::rules($tag))->safe();

        $attributes = [
            'name' => $input->string('name')->toString(),
            'slug' => $input->string('slug')->toString(),
        ];

        if ($input->has('color')) {
            $attributes['color'] = $input->filled('color') ? $input->string('color')->toString() : null;
        }

        if ($input->has('legacy_nest_id')) {
            $attributes['legacy_nest_id'] = $input->filled('legacy_nest_id') ? $input->integer('legacy_nest_id') : null;
        }

        return $attributes;
    }

    /**
     * @param  ActionInput  $input
     */
    public function create(array $input): Tag
    {
        return Tag::query()->create(self::attributes($input));
    }
}
