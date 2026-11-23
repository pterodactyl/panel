<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Service\Database;

use Pterodactyl\Exceptions\PterodactylException;

class DatabaseClientFeatureNotEnabledException extends PterodactylException
{
    public function __construct()
    {
        parent::__construct(__('exceptions.databases.client_creation_disabled'));
    }
}
