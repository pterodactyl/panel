<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class BlockDeleteAllocationRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminAllocationsDelete];
    }

    /**
     * `ip` is required (not legacy `sometimes`) so a malformed body 422s rather than silently 204-ing.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'ip' => ['required', 'string'],
        ];
    }
}
