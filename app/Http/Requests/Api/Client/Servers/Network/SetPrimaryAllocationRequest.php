<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Network;

class SetPrimaryAllocationRequest extends UpdateAllocationRequest
{
    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [];
    }
}
