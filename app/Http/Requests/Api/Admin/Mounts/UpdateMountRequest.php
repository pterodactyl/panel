<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Mounts;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Mount;
use Pterodactyl\Validation\MountRules;

class UpdateMountRequest extends StoreMountRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminMountsUpdate];
    }

    /**
     * Validation rules for updating a mount.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return MountRules::rules($this->parameter('mount', Mount::class));
    }
}
