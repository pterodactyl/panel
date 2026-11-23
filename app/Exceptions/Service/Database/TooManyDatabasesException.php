<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Service\Database;

use Pterodactyl\Exceptions\DisplayException;

class TooManyDatabasesException extends DisplayException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.databases.too_many'));
    }
}
