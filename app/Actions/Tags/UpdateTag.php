<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Tags;

use Pterodactyl\Contracts\Tags\UpdatesTags;
use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Tag;

final readonly class UpdateTag implements UpdatesTags
{
    /**
     * Update an existing tag from validated attributes. A built-in game tag's slug is
     * the key feature gating matches on and its presentation comes from the
     * EggSpecificTags enum, so it must never be renamed or relabelled.
     *
     * @param  TagData  $data
     *
     * @throws DisplayException
     */
    public function update(Tag $tag, array $data): Tag
    {
        throw_if(EggSpecificTags::isSpecial($tag->slug), DisplayException::class, 'Built-in game tags cannot be edited.');

        $tag->update($data);

        return $tag;
    }
}
