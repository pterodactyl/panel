<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Mounts;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Mount;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Validation\MountRules;

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
        return MountRules::rules();
    }

    /**
     * Model attributes for the mount. Optional fields stay absent when the
     * request omits them so an update leaves the stored value alone.
     *
     * @return MountData
     */
    public function payload(): array
    {
        $payload = [
            'name' => $this->string('name')->toString(),
            'source' => $this->string('source')->toString(),
            'target' => $this->string('target')->toString(),
            'extensions' => $this->extensionValues(),
        ];

        if ($this->exists('description')) {
            $payload['description'] = JsonValueGuard::nullableString($this->input('description'));
        }

        if ($this->exists('read_only')) {
            $payload['read_only'] = $this->boolean('read_only');
        }

        if ($this->exists('user_mountable')) {
            $payload['user_mountable'] = $this->boolean('user_mountable');
        }

        return $payload;
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Model|string
    {
        return Mount::class;
    }
}
