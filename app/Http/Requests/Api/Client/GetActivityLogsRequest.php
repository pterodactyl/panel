<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client;

use Pterodactyl\Validation\ActivityLogRules;

class GetActivityLogsRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return ActivityLogRules::rules();
    }
}
