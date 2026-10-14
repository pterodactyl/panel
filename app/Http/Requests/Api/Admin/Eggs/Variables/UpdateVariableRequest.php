<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables;

use Pterodactyl\Actions\Eggs\Variables\CreateEggVariable;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;

class UpdateVariableRequest extends StoreVariableRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggVariablesUpdate];
    }

    /**
     * Validation rules for updating an egg variable, which keeps its own environment
     * variable name without a uniqueness clash.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return CreateEggVariable::rules($this->parameter('egg', Egg::class), $this->parameter('variable', EggVariable::class));
    }
}
