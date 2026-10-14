<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Mounts;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Actions\Mounts\CreateMount;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Mount;

class StoreMountRequest extends AdminApiRequest
{
    use ValidatesExtensionFields;

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
        return CreateMount::rules();
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Model|string
    {
        return Mount::class;
    }
}
