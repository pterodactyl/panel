<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs;

use Pterodactyl\Actions\Eggs\Scripts\UpdateInstallScript;
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
        return UpdateInstallScript::rules();
    }
}
