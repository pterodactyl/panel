<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Tags;

use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Tags\UpdatesTags;
use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Tag;

final readonly class UpdateTag implements UpdatesTags
{
    /**
     * Update an existing tag. A built-in game tag's slug is the key feature gating matches on
     * and its presentation comes from the EggSpecificTags enum, so it must never be renamed or
     * relabelled.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws DisplayException
     */
    public function update(Tag $tag, array $input): Tag
    {
        throw_if(EggSpecificTags::isSpecial($tag->slug), DisplayException::class, 'Built-in game tags cannot be edited.');

        $validated = Validator::validate($input, CreateTag::rules($tag));
        // Blank strings are null, as they are in a request, so a blank colour or legacy nest id
        // is cleared. The colour and legacy nest id it is not given keep their stored values.
        $validated = array_map(fn (mixed $value): mixed => is_string($value) && blank($value) ? null : $value, $validated);

        $tag->update($validated);

        return $tag;
    }
}
