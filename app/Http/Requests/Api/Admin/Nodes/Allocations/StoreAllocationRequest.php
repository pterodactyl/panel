<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class StoreAllocationRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminAllocationsCreate];
    }

    /**
     * Validation rules for assigning new allocations to a node.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'ip' => ['required', 'array', 'list'],
            'ip.*' => ['string'],
            'alias' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ports' => ['required', 'array', 'list'],
            'ports.*' => ['string'],
        ];
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'ip' => 'IP Address',
            'alias' => 'IP Alias',
            'ports' => 'Ports',
        ];
    }
}
