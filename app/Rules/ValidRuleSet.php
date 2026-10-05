<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use BadMethodCallException;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Validates that a rule string is one Laravel's validator can resolve.
 */
class ValidRuleSet implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        try {
            Validator::make(['__TEST' => 'test'], ['__TEST' => $value])->fails();
        } catch (BadMethodCallException $badMethodCallException) {
            $matches = [];
            $rule = preg_match('/Method (.+) does not exist\./', $badMethodCallException->getMessage(), $matches) === 1
                ? Str::snake(Str::after(Str::afterLast($matches[1], '::'), 'validate'))
                : $value;

            $fail('exceptions.egg.variables.bad_validation_rule')->translate(['rule' => $rule]);
        }
    }
}
