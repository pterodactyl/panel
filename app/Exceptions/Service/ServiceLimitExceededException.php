<?php

namespace Pterodactyl\Exceptions\Service;

use Pterodactyl\Exceptions\DisplayException;

class ServiceLimitExceededException extends DisplayException
{
    /**
     * Exception thrown when something goes over a defined limit, such as allocated
     * ports, tasks, databases, etc.
     */
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, $previous, self::LEVEL_WARNING);
    }
}
