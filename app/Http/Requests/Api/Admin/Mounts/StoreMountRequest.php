<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Mounts;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Validation\MountRules;

class StoreMountRequest extends AdminApiRequest
{
    use ValidatesExtensionFields;

    public function permissions(): array
    {
        return [Permissions::AdminMountsCreate];
    }

    /**
     * {@inheritdoc}
     */
    public function extensionForm(): string
    {
        return 'admin.mount';
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
