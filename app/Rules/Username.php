<?php

declare(strict_types=1);

namespace Pterodactyl\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Username implements ValidationRule
{
    /**
     * Regex to use when validating usernames.
     */
    public const string VALIDATION_REGEX = '/^[a-z0-9]([\w\.-]+)[a-z0-9]$/';

    /**
     * Validate that a username contains only the allowed characters and starts/ends
     * with alphanumeric characters.
     *
     * Allowed characters: a-z0-9_-.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match(self::VALIDATION_REGEX, mb_strtolower($value)) === 1) {
            return;
        }

        $fail('The :attribute must start and end with alpha-numeric characters and contain only letters, numbers, dashes, underscores, and periods.');
    }
}
