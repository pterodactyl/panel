<?php

declare(strict_types=1);

namespace Pterodactyl\Support;

final class ValidationRuleSubset
{
    /**
     * @param  NormalizedValidationRules  $rules
     * @param  list<string>  $keys
     * @return NormalizedValidationRules
     */
    public static function select(array $rules, array $keys): array
    {
        $selected = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $rules)) {
                $selected[$key] = $rules[$key];
            }
        }

        return $selected;
    }
}
