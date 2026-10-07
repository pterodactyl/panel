<?php

declare(strict_types=1);

namespace Pterodactyl\Data;

use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;
use Pterodactyl\Support\JsonEmptyObject;
use UnexpectedValueException;

final class ServerCreationData
{
    /**
     * @param  JsonInputValue  $value
     * @return ServerCreationAttributes
     */
    public static function parse(mixed $value): array
    {
        throw_unless(is_array($value), UnexpectedValueException::class, 'Validated server creation data must be an array.');

        return [
            'external_id' => self::nullableString($value['external_id'] ?? null, 'external_id'),
            'name' => self::string($value['name'] ?? null, 'name'),
            'description' => self::nullableString($value['description'] ?? null, 'description'),
            'owner_id' => self::integer($value['owner_id'] ?? null, 'owner_id'),
            'egg_id' => self::integer($value['egg_id'] ?? null, 'egg_id'),
            'image' => self::string($value['image'] ?? null, 'image'),
            'startup' => self::string($value['startup'] ?? null, 'startup'),
            'environment' => self::environment($value['environment'] ?? []),
            'memory' => self::integer($value['memory'] ?? null, 'memory'),
            'swap' => self::integer($value['swap'] ?? null, 'swap'),
            'disk' => self::integer($value['disk'] ?? null, 'disk'),
            'io' => self::integer($value['io'] ?? null, 'io'),
            'cpu' => self::integer($value['cpu'] ?? null, 'cpu'),
            'threads' => self::nullableString($value['threads'] ?? null, 'threads'),
            'skip_scripts' => self::boolean($value['skip_scripts'] ?? false, 'skip_scripts'),
            'allocation_id' => self::nullableInteger($value['allocation_id'] ?? null, 'allocation_id'),
            'allocation_additional' => self::nullableIntegerList($value['allocation_additional'] ?? null, 'allocation_additional'),
            'start_on_completion' => self::boolean($value['start_on_completion'] ?? false, 'start_on_completion'),
            'database_limit' => self::nullableInteger($value['database_limit'] ?? null, 'database_limit'),
            'allocation_limit' => self::nullableInteger($value['allocation_limit'] ?? null, 'allocation_limit'),
            'backup_limit' => self::nullableInteger($value['backup_limit'] ?? null, 'backup_limit'),
            'oom_disabled' => self::nullableBoolean($value['oom_disabled'] ?? null, 'oom_disabled'),
            'extensions' => ExtensionSettingValueGuard::fieldInput($value['extensions'] ?? []),
        ];
    }

    /** @param JsonInputValue $value */
    private static function string(mixed $value, string $key): string
    {
        if (! is_string($value)) {
            throw self::invalid($key, 'a string');
        }

        return $value;
    }

    /** @param JsonInputValue $value */
    private static function nullableString(mixed $value, string $key): ?string
    {
        return $value === null ? null : self::string($value, $key);
    }

    /** @param JsonInputValue $value */
    private static function integer(mixed $value, string $key): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value) || preg_match('/^-?\d+$/', $value) !== 1) {
            throw self::invalid($key, 'an integer');
        }

        // SAFETY: the anchored decimal-integer pattern above proves this string has an integer representation.
        return (int) $value;
    }

    /** @param JsonInputValue $value */
    private static function nullableInteger(mixed $value, string $key): ?int
    {
        return $value === null ? null : self::integer($value, $key);
    }

    /** @param JsonInputValue $value */
    private static function boolean(mixed $value, string $key): bool
    {
        return match ($value) {
            true, 1, '1' => true,
            false, 0, '0' => false,
            default => throw self::invalid($key, 'a boolean'),
        };
    }

    /** @param JsonInputValue $value */
    private static function nullableBoolean(mixed $value, string $key): ?bool
    {
        return $value === null ? null : self::boolean($value, $key);
    }

    /**
     * @param  JsonInputValue  $value
     * @return array<string, ApiScalar>
     */
    private static function environment(mixed $value): array
    {
        if (! is_array($value)) {
            throw self::invalid('environment', 'an object of scalar values');
        }

        $environment = [];
        foreach ($value as $key => $item) {
            if (! is_string($key) || is_array($item) || $item instanceof JsonEmptyObject) {
                throw self::invalid('environment', 'an object of scalar values');
            }

            $environment[$key] = $item;
        }

        return $environment;
    }

    /**
     * @param  JsonInputValue  $value
     * @return list<int>|null
     */
    private static function nullableIntegerList(mixed $value, string $key): ?array
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value) || ! array_is_list($value)) {
            throw self::invalid($key, 'a list of integers');
        }

        $integers = [];
        foreach ($value as $item) {
            $integers[] = self::integer($item, $key);
        }

        return $integers;
    }

    private static function invalid(string $key, string $expected): UnexpectedValueException
    {
        return new UnexpectedValueException(sprintf('Validated server creation field "%s" must be %s.', $key, $expected));
    }
}
