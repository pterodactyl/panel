<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Pterodactyl\Models\EggVariable;

class UnreservedEnvironmentVariable implements ValidationRule
{
    /**
     * Wings sets the reserved names itself, so an egg variable can never use one,
     * in any letter case.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && in_array(mb_strtoupper($value), explode(',', EggVariable::RESERVED_ENV_NAMES), true)) {
            $fail(trans('exceptions.service.variables.reserved_name', ['name' => $value]));
        }
    }

    /**
     * Describes the rule in the generated API documentation.
     *
     * @return array{description: string}
     */
    public function docs(): array
    {
        $names = array_map(fn (string $name): string => "<code>{$name}</code>", explode(',', EggVariable::RESERVED_ENV_NAMES));

        return ['description' => sprintf('Must not be one of %s, in any letter case.', implode(', ', $names))];
    }
}
