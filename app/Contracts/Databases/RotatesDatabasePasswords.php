<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Databases;

use Pterodactyl\Models\Database;
use Throwable;

interface RotatesDatabasePasswords
{
    /** @throws Throwable */
    public function rotate(Database $database): string;
}
