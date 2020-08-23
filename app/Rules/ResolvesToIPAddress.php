<?php

namespace Pterodactyl\Rules;

use Illuminate\Contracts\Validation\Rule;

class ResolvesToIPAddress implements Rule
{
    /**
     * Validate that a given string can correctly resolve to a valid IPv4 address.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // inet_pton returns false if the value passed through is not a valid IP address, so we'll just
        // use that a nice ugly PHP hack to determine if we should pass this off to the gethostbyname
        // call below.
        $isIP = inet_pton($attribute) !== false;

        // If the value received is not an IP address try to look it up using the gethostbyname() call.
        // If that returns the same value that we passed in then it means it did not resolve to anything
        // and we should fail this validation call.
        return $isIP || gethostbyname($value) !== $value;
    }

    /**
     * Return a validation message for use when this rule fails.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be a valid IPv4 address or hostname that resolves to a valid IPv4 address.';
    }

    /**
     * Convert the rule to a validation string. This is necessary to avoid
     * issues with Eloquence which tries to use this rule as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return 'p_resolves_to_ip_address';
    }
}
