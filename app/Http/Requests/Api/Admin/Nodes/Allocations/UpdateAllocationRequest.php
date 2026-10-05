<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Nodes\Allocations;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class UpdateAllocationRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminAllocationsUpdate];
    }

    /**
     * Validation rules for updating an allocation's alias.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'alias' => ['present', 'nullable', 'string'],
        ];
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'alias' => 'IP Alias',
        ];
    }
}
