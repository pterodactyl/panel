<?php

namespace Pterodactyl\Http\Requests\Api\Client;

use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

abstract class ClientApiRequest extends ApplicationApiRequest
{
    /**
     * Determine if the current user is authorized to perform
     * the requested action aganist the API.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }
}
