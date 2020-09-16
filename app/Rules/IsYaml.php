<?php

namespace Pterodactyl\Rules;

use Illuminate\Contracts\Validation\Rule;
use Symfony\Component\Yaml\Yaml as Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class IsYaml implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        try {
            Yaml::parse($value);
        } catch (ParseException $ignored) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attributes must be valid YAML.';
    }
}
