<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Service\Allocation;

use Pterodactyl\Exceptions\DisplayException;

class InvalidPortMappingException extends DisplayException
{
    /** @param ApiScalar $port */
    public function __construct(mixed $port)
    {
        parent::__construct(trans('exceptions.allocations.invalid_mapping', ['port' => $port]));
    }
}
