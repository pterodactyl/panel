<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Network;

use Pterodactyl\Actions\Allocations\CreateAllocations;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class UpdateAllocationRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permissions::AllocationUpdate->value;
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'notes' => [...CreateAllocations::NOTES, 'present'],
        ];
    }
}
