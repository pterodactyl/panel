<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;

interface UpdatesMounts
{
    /**
     * Update an existing mount. The input is validated here, so it can come straight from any
     * caller. It takes the fields a mount is created with, required ones included; the
     * description and flags keep their stored values when they are left out. Extension field
     * values go under `extensions` as ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  the fields a mount is created with
     */
    public function update(Mount $mount, array $input): Mount;
}
