<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client;

class GetExtensionProgressRequest extends ClientApiRequest
{
    /** @return ValidationRules */
    public function rules(): array
    {
        return [];
    }
}
