<?php

declare(strict_types=1);

namespace Pterodactyl\Exceptions\Service\Allocation;

use Pterodactyl\Exceptions\DisplayException;

class AutoAllocationNotEnabledException extends DisplayException
{
    /**
     * AutoAllocationNotEnabledException constructor.
     */
    public function __construct()
    {
        parent::__construct(
            __('exceptions.allocations.auto_allocation_disabled')
        );
    }
}
