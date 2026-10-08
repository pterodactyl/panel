<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Tags;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Tag;

interface UpdatesTags
{
    /**
     * Update an existing tag from validated attributes. Built-in game tags cannot be edited.
     *
     * @param  TagData  $data
     *
     * @throws DisplayException
     */
    public function update(Tag $tag, array $data): Tag;
}
