<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Pterodactyl\Services\Extensions\ExtensionFieldValueGuard;

class ExtensionFieldValue implements ValidationRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! ExtensionFieldValueGuard::isValue($value)) {
            $fail('The :attribute field must be a string, number, boolean, null or a list of strings and numbers.');
        }
    }
}
