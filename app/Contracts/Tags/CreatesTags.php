<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Tags;

use Pterodactyl\Models\Tag;

interface CreatesTags
{
    /**
     * The input is validated here.
     *
     * @param  array<string, mixed>  $input  name and slug, and optionally color and legacy_nest_id
     */
    public function create(array $input): Tag;
}
