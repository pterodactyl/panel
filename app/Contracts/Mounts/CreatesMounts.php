<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;

interface CreatesMounts
{
    /**
     * Create a new mount from validated attributes, assigning it a fresh UUID.
     *
     * @param  MountData  $data
     */
    public function create(array $data): Mount;
}
