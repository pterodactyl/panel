<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Stringable;
use UnexpectedValueException;

final class ExtensionFieldValueGuard
{
    /**
     * @phpstan-assert-if-true ExtensionFieldValue $value
     */
    public static function isValue(mixed $value): bool
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return true;
        }

        if (! is_array($value) || ! array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_int($item) && ! is_float($item) && ! is_string($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @phpstan-assert ExtensionFieldValues $value
     */
    public static function assertValues(mixed $value): void
    {
        throw_unless(is_array($value), UnexpectedValueException::class, 'Extension field values must be an object.');

        foreach ($value as $key => $item) {
            throw_unless(is_string($key), UnexpectedValueException::class, 'Extension field names must be strings.');
            throw_unless(self::isValue($item), UnexpectedValueException::class, sprintf('The value of the field "%s" must be a string, number, boolean, null or a list of strings and numbers.', $key));
        }
    }

    /**
     * @param  array<array-key, ApiValue9>  $value
     * @return ExtensionFormValues
     */
    public static function formValues(array $value): array
    {
        $values = [];
        foreach ($value as $identifier => $fields) {
            if (! is_string($identifier) || ! is_array($fields)) {
                continue;
            }

            $values[$identifier] = [];
            foreach ($fields as $key => $field) {
                if (is_string($key) && self::isValue($field)) {
                    $values[$identifier][$key] = $field;
                }
            }
        }

        return $values;
    }

    /**
     * @param  ValidationRule|ValidationRuleSet  $rule
     * @return ValidationRuleSet
     */
    public static function ruleSet(string|Stringable|Closure|Rule|ValidationRule|array $rule): array
    {
        return match (true) {
            is_array($rule) => array_values($rule),
            is_string($rule) => explode('|', $rule),
            default => [$rule],
        };
    }
}
