<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;

interface UpdatesMounts
{
    /**
     * Update an existing mount from validated attributes.
     *
     * @param  MountData  $data
     */
    public function update(Mount $mount, array $data): Mount;
}
