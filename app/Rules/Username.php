<?php

namespace Pterodactyl\Rules;

use Illuminate\Contracts\Validation\Rule;

class Username implements Rule
{
    /**
     * Regex to use when validating usernames.
     */
    public const VALIDATION_REGEX = '/^[a-z0-9]([\w\.-]+)[a-z0-9]$/';

    /**
     * Validate that a username contains only the allowed characters and starts/ends
     * with alphanumeric characters.
     *
     * Allowed characters: a-z0-9_-.
     *
     * @param string $attribute
     */
    public function passes($attribute, $value): bool
    {
        return preg_match(self::VALIDATION_REGEX, mb_strtolower($value));
    }

    /**
     * Return a validation message for use when this rule fails.
     */
    public function message(): string
    {
        return 'The :attribute must start and end with alpha-numeric characters and
                contain only letters, numbers, dashes, underscores, and periods.';
    }

    /**
     * Convert the rule to a validation string. This is necessary to avoid
     * issues with Eloquence which tries to use this rule as a string.
     */
    public function __toString(): string
    {
        return 'p_username';
    }
}
