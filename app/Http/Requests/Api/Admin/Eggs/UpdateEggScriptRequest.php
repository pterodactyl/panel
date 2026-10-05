<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;

class UpdateEggScriptRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggsUpdate];
    }

    /**
     * Validation rules for updating an egg's install script.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'script_install' => ['sometimes', 'nullable', 'string'],
            'script_is_privileged' => ['sometimes', 'required', 'boolean'],
            'script_entry' => ['sometimes', 'required', 'string'],
            'script_container' => ['sometimes', 'required', 'string'],
            'copy_script_from' => ['sometimes', 'nullable', 'numeric'],
        ];
    }
}
