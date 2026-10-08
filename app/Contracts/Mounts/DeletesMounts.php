<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Mounts;

use Pterodactyl\Models\Mount;

interface DeletesMounts
{
    /**
     * Delete a mount.
     */
    public function delete(Mount $mount): void;
}
