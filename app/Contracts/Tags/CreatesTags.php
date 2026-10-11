<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Tags;

use Pterodactyl\Models\Tag;

interface CreatesTags
{
    /**
     * Create a new tag. The input is validated here, so it can come straight from any caller.
     *
     * @param  ActionInput  $input  name and slug, and optionally color and legacy_nest_id
     */
    public function create(array $input): Tag;
}
