<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers;

use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class GetServerRequest extends ClientApiRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
