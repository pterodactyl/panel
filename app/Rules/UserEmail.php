<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserEmail implements ValidationRule
{
    /**
     * Validate that the local part of an email address does not start with a dash, quoted
     * or not, so the address can never be read as a command-line option.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $local = mb_trim(explode('@', $value, 2)[0], '"');
        if (str_starts_with($local, '-')) {
            $fail('The :attribute must not start with a dash.');
        }
    }
}
