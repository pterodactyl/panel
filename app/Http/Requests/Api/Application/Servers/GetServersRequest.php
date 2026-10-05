<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

class GetServersRequest extends GetServerRequest
{
    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'search' => ['string', 'max:100'],
        ];
    }
}
