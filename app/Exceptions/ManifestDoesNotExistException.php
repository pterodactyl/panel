<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions;

use Exception;

class ManifestDoesNotExistException extends Exception
{
    public function __construct()
    {
        parent::__construct('The Vite manifest has not been generated yet. Run "npm run build:production" to build the frontend first.');
    }
}
