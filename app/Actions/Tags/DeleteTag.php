<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Tags;

use Pterodactyl\Contracts\Tags\DeletesTags;
use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Tag;

final readonly class DeleteTag implements DeletesTags
{
    public function delete(Tag $tag): void
    {
        // The cascading pivot cleanup would discard every egg and node assignment of a built-in
        // tag, and recreating the tag later cannot restore them.
        throw_if(EggSpecificTags::isSpecial($tag->slug), DisplayException::class, 'Built-in game tags cannot be deleted.');

        $tag->delete();
    }
}
