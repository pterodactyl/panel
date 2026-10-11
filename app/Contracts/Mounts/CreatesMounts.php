<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;

interface CreatesMounts
{
    /**
     * Create a new mount, assigning it a fresh UUID. The input is validated here, so it can
     * come straight from any caller; extension field values go under `extensions` as
     * ValidatedExtensionValues.
     *
     * @param  ActionInput  $input  name, source and target, and optionally description, read_only and user_mountable
     */
    public function create(array $input): Mount;
}
