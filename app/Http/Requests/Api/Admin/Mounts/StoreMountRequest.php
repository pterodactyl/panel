<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Mounts;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Validation\MountRules;

class StoreMountRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminMountsCreate];
    }

    /**
     * Validation rules for creating a mount.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return MountRules::rules();
    }
}
