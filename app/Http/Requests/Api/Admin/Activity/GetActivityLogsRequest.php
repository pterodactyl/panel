<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Activity;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Validation\ActivityLogRules;

class GetActivityLogsRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminActivityRead];
    }

    public function rules(): array
    {
        return ActivityLogRules::rules();
    }
}
