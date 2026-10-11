<?php

declare(strict_types=1);

namespace Pterodactyl\Validation;

use Pterodactyl\Rules\UnreservedEnvironmentVariable;
use Pterodactyl\Rules\ValidRuleSet;

final class EggVariableRules
{
    /**
     * Canonical validation rules for an egg variable's own fields, whether it is
     * written through the admin API or arrives in an imported egg.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:191'],
            'description' => ['sometimes', 'nullable', 'string'],
            'env_variable' => ['required', 'regex:/^[\w]{1,191}$/', new UnreservedEnvironmentVariable],
            'rules' => ['bail', 'required', 'string', new ValidRuleSet],
            'default_value' => ['present', 'string'],
        ];
    }
}
