<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Tags;

use Pterodactyl\Models\Tag;

interface CreatesTags
{
    /**
     * Create a new tag from validated attributes.
     *
     * @param  TagData  $data
     */
    public function create(array $data): Tag;
}
