<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class DeleteAllocationRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminAllocationsDelete];
    }

    /**
     * Single delete resolves the allocation from the route; bulk/IP-block deletes have their own request classes.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [];
    }
}
