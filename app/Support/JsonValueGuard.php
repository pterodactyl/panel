<?php

declare(strict_types=1);

namespace Pterodactyl\Support;

use UnexpectedValueException;

final class JsonValueGuard
{
    /**
     * @return JsonValue
     */
    public static function decode(string $encoded, int $maximumDepth = 10): bool|float|int|string|array|null
    {
        $value = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);
        self::assertValue($value, $maximumDepth);

        return $value;
    }

    /** @return ApiValue8 */
    public static function decode8(string $encoded): bool|float|int|string|array|null
    {
        $value = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);
        self::assertValue8($value);

        return $value;
    }

    /** @return array<array-key, ApiValue7> */
    public static function decodeArray8(string $encoded): array
    {
        $value = self::decode8($encoded);
        throw_unless(is_array($value), UnexpectedValueException::class, 'The JSON value must be an array.');

        return $value;
    }

    /**
     * @phpstan-assert JsonValue $value
     */
    public static function assertValue(mixed $value, int $remainingDepth = 10): void
    {
        if ($value === null || is_bool($value) || is_float($value) || is_int($value) || is_string($value)) {
            return;
        }

        if ($value instanceof JsonEmptyObject) {
            return;
        }

        throw_if(! is_array($value) || $remainingDepth === 0, UnexpectedValueException::class, 'JSON values must be scalars or arrays nested at most 10 levels.');

        foreach ($value as $nestedValue) {
            self::assertValue($nestedValue, $remainingDepth - 1);
        }
    }

    /** @phpstan-assert ApiPayload $value */
    public static function assertPayload(mixed $value): void
    {
        self::assertValue($value);
        throw_unless(is_array($value), UnexpectedValueException::class, 'API payloads must be arrays.');

        foreach (array_keys($value) as $key) {
            throw_unless(is_string($key), UnexpectedValueException::class, 'API payload keys must be strings.');
        }
    }

    /** @phpstan-assert ApiScalar $value */
    public static function assertScalar(mixed $value): void
    {
        throw_if($value !== null && ! is_bool($value) && ! is_float($value) && ! is_int($value) && ! is_string($value), UnexpectedValueException::class, 'Expected a scalar JSON value.');
    }

    /** @phpstan-assert string $value */
    public static function string(mixed $value): string
    {
        throw_unless(is_string($value), UnexpectedValueException::class, 'Expected a string value.');

        return $value;
    }

    /**
     * @phpstan-assert array<array-key, ApiValue9> $value
     *
     * @return array<array-key, ApiValue9>
     */
    public static function jsonArray(mixed $value): array
    {
        self::assertValue($value);
        throw_unless(is_array($value), UnexpectedValueException::class, 'Expected a JSON array.');

        return $value;
    }

    /**
     * @phpstan-assert list<string> $value
     *
     * @return list<string>
     */
    public static function stringList(mixed $value): array
    {
        throw_if(! is_array($value) || ! array_is_list($value), UnexpectedValueException::class, 'Expected a list of strings.');

        foreach ($value as $item) {
            throw_unless(is_string($item), UnexpectedValueException::class, 'Expected a list of strings.');
        }

        return $value;
    }

    /** @phpstan-assert array<string, ApiScalar> $value */
    public static function assertScalarPayload(mixed $value): void
    {
        throw_unless(is_array($value), UnexpectedValueException::class, 'Expected a scalar payload.');

        foreach ($value as $key => $item) {
            throw_unless(is_string($key), UnexpectedValueException::class, 'Scalar payload keys must be strings.');

            self::assertScalar($item);
        }
    }

    /** @phpstan-assert array<string, ApiValue9> $value */
    public static function assertPayload9(mixed $value): void
    {
        self::assertValue($value, 9);
        throw_unless(is_array($value), UnexpectedValueException::class, 'OpenAPI schema payloads must be arrays.');

        foreach (array_keys($value) as $key) {
            throw_unless(is_string($key), UnexpectedValueException::class, 'OpenAPI schema keys must be strings.');
        }
    }

    /** @phpstan-assert ApiValue8 $value */
    private static function assertValue8(mixed $value): void
    {
        self::assertValue($value, 8);
    }
}
