<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Databases;

use Exception;
use Pterodactyl\Models\Database;

interface DeletesDatabases
{
    /** @throws Exception */
    public function delete(Database $database): ?bool;
}
