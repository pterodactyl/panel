<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Tags;

use Pterodactyl\Contracts\Tags\DeletesTags;
use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Tag;

final readonly class DeleteTag implements DeletesTags
{
    /**
     * Delete a tag and detach it from every egg and node. Built-in game tags cannot be
     * deleted because the cascading pivot cleanup would discard every egg and node
     * assignment, and recreating the tag later cannot restore those associations.
     *
     * @throws DisplayException
     */
    public function delete(Tag $tag): void
    {
        throw_if(EggSpecificTags::isSpecial($tag->slug), DisplayException::class, 'Built-in game tags cannot be edited.');

        $tag->delete();
    }
}
