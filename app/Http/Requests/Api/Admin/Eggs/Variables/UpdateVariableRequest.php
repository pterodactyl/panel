<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Eggs\Variables;

use Illuminate\Validation\Rules\Unique;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\EggVariable;

class UpdateVariableRequest extends StoreVariableRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminEggVariablesUpdate];
    }

    protected function uniqueEnvironmentVariable(): Unique
    {
        return parent::uniqueEnvironmentVariable()->ignore($this->parameter('variable', EggVariable::class));
    }
}
