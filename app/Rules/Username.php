<?php

namespace Pterodactyl\Rules;

use Illuminate\Contracts\Validation\Rule;

class Username implements Rule
{
    /**
     * Regex to use when validating usernames.
     */
    public const VALIDATION_REGEX = '/^[a-zA-Z0-9_\-.]{3,16}$/';

    /**
     * Validate that a username contains only the allowed characters and starts/ends
     * with alpha-numeric characters.
     *
     * Allowed characters: a-z0-9_-.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return preg_match(self::VALIDATION_REGEX, $value);
    }

    /**
     * Return a validation message for use when this rule fails.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be consisting of alpha-numeric characters and
                can only contain letters, numbers, dashes, underscores, and periods.';
    }

    /**
     * Convert the rule to a validation string. This is necessary to avoid
     * issues with Eloquence which tries to use this rule as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return 'p_username';
    }
}
