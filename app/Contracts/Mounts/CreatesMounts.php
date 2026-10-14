<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;

interface CreatesMounts
{
    /**
     * The mount gets a fresh UUID. The input is validated here. Extension field values go under
     * `extensions` as ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  name, source and target, and optionally description, read_only and user_mountable
     */
    public function create(array $input): Mount;
}
