<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables;

use Pterodactyl\Actions\Eggs\Variables\CreateEggVariable;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Models\Egg;

class StoreVariableRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggVariablesCreate];
    }

    /**
     * Validation rules for creating an egg variable.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return CreateEggVariable::rules($this->parameter('egg', Egg::class));
    }
}
