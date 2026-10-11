<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Tags;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Tag;

interface UpdatesTags
{
    /**
     * Update an existing tag. Built-in game tags cannot be edited. The input is validated here,
     * so it can come straight from any caller. It takes the fields a tag is created with,
     * required ones included; the colour and legacy nest id keep their stored values when they
     * are left out.
     *
     * @param  ActionInput  $input  the fields a tag is created with
     *
     * @throws DisplayException
     */
    public function update(Tag $tag, array $input): Tag;
}
