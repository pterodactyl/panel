<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Mounts;

use Pterodactyl\Actions\Mounts\CreateMount;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Mount;

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
        return CreateMount::rules($this->parameter('mount', Mount::class));
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Mount
    {
        return $this->parameter('mount', Mount::class);
    }
}
