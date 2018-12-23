<?php

namespace Pterodactyl\Exceptions\Service\Allocation;

use Pterodactyl\Exceptions\DisplayException;

class InvalidPortMappingException extends DisplayException
{
    /**
     * InvalidPortMappingException constructor.
     *
     * @param mixed $port
     */
    public function __construct($port)
    {
        parent::__construct(trans('exceptions.allocations.invalid_mapping', ['port' => $port]));
    }
}
