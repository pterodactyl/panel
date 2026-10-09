<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;

/** An extension field's value: a string, number, boolean or null, or a list of those. */
final class ExtensionFieldValue implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! ExtensionSettingValueGuard::isFieldValue($value)) {
            $fail('The :attribute field must be a string, number, boolean, or a list of them.');
        }
    }
}
