<?php

namespace App\Http\Requests\Api\Client;

use App\Http\Requests\Api\Application\ApplicationApiRequest;

abstract class ClientApiRequest extends ApplicationApiRequest
{
    /**
     * Determine if the current user is authorized to perform
     * the requested action against the API.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }
}
