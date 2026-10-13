<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Tags;

use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Models\Tag;

interface UpdatesTags
{
    /**
     * Built-in game tags cannot be edited. The input is validated here and takes the fields a
     * tag is created with, required ones included; the colour and legacy nest id keep their
     * stored values when they are left out.
     *
     * @param  array<string, mixed>  $input  the fields a tag is created with
     *
     * @throws DisplayException
     */
    public function update(Tag $tag, array $input): Tag;
}
