<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Tags;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Tag;

interface DeletesTags
{
    /**
     * The tag is detached from every egg and node. Built-in game tags cannot be deleted.
     *
     * @throws DisplayException
     */
    public function delete(Tag $tag): void;
}
