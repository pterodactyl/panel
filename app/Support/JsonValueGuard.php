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

    /**
     * @phpstan-assert ApiScalar $value
     *
     * @return ApiScalar
     */
    public static function scalar(mixed $value): bool|float|int|string|null
    {
        self::assertScalar($value);

        return $value;
    }

    /** @phpstan-assert string $value */
    public static function string(mixed $value): string
    {
        throw_unless(is_string($value), UnexpectedValueException::class, 'Expected a string value.');

        return $value;
    }

    /**
     * @phpstan-assert non-empty-string $value
     *
     * @return non-empty-string
     */
    public static function nonEmptyString(mixed $value): string
    {
        $value = self::string($value);
        throw_if($value === '', UnexpectedValueException::class, 'Expected a non-empty string value.');

        return $value;
    }

    /** @phpstan-assert string|null $value */
    public static function nullableString(mixed $value): ?string
    {
        throw_if($value !== null && ! is_string($value), UnexpectedValueException::class, 'Expected a nullable string value.');

        return $value;
    }

    /** @phpstan-assert ApiScalar $value */
    public static function nullableScalarString(mixed $value): ?string
    {
        $value = self::scalar($value);

        // SAFETY: assertScalar() limits the value to JSON scalars, all of which have a defined string representation.
        return $value === null ? null : (string) $value;
    }

    /** @phpstan-assert bool $value */
    public static function boolean(mixed $value): bool
    {
        throw_unless(is_bool($value), UnexpectedValueException::class, 'Expected a boolean value.');

        return $value;
    }

    /** @phpstan-assert int|null $value */
    public static function nullableInteger(mixed $value): ?int
    {
        throw_if($value !== null && ! is_int($value), UnexpectedValueException::class, 'Expected a nullable integer value.');

        return $value;
    }

    /** @phpstan-assert int|string $value */
    public static function integer(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);
            if ($integer !== false) {
                return $integer;
            }
        }

        throw new UnexpectedValueException('Expected an integer value.');
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

    /**
     * @phpstan-assert list<int> $value
     *
     * @return list<int>
     */
    public static function integerList(mixed $value): array
    {
        throw_if(! is_array($value) || ! array_is_list($value), UnexpectedValueException::class, 'Expected a list of integers.');

        foreach ($value as $item) {
            throw_unless(is_int($item), UnexpectedValueException::class, 'Expected a list of integers.');
        }

        return $value;
    }

    /**
     * @param  list<int|string>  $value
     * @return list<int>
     */
    public static function normalizedIntegerList(array $value): array
    {
        return array_map(self::integer(...), $value);
    }

    /**
     * @phpstan-assert list<int|string> $value
     *
     * @return list<int|string>
     */
    public static function integerStringList(mixed $value): array
    {
        throw_if(! is_array($value) || ! array_is_list($value), UnexpectedValueException::class, 'Expected a list of integers or strings.');

        foreach ($value as $item) {
            throw_if(! is_int($item) && ! is_string($item), UnexpectedValueException::class, 'Expected a list of integers or strings.');
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

    /** @phpstan-assert OpenApiSchemaMap $value */
    public static function assertOpenApiSchemaMap(mixed $value): void
    {
        throw_unless(is_array($value), UnexpectedValueException::class, 'OpenAPI schema maps must be arrays.');

        foreach ($value as $key => $schema) {
            throw_unless(is_string($key), UnexpectedValueException::class, 'OpenAPI schema map keys must be strings.');

            self::assertPayload9($schema);
        }
    }

    /** @phpstan-assert OpenApiSchemaInput $value */
    public static function assertOpenApiSchemaInput(mixed $value): void
    {
        throw_unless(is_array($value), UnexpectedValueException::class, 'OpenAPI schemas must be arrays.');

        foreach ($value as $key => $item) {
            throw_unless(is_string($key), UnexpectedValueException::class, 'OpenAPI schema keys must be strings.');

            if ($key === 'properties') {
                self::assertOpenApiSchemaMap($item);
            } else {
                self::assertValue($item, 8);
            }
        }
    }

    /** @phpstan-assert OpenApiOperation $value */
    public static function assertOpenApiOperation(mixed $value): void
    {
        throw_unless(is_array($value), UnexpectedValueException::class, 'OpenAPI operations must be arrays.');

        foreach (array_keys($value) as $key) {
            throw_unless(is_string($key), UnexpectedValueException::class, 'OpenAPI operation keys must be strings.');
        }

    }

    /** @phpstan-assert OpenApiContent $value */
    public static function assertOpenApiContent(mixed $value): void
    {
        throw_unless(is_array($value), UnexpectedValueException::class, 'OpenAPI content must be an array.');

        foreach ($value as $contentType => $media) {
            throw_if(! is_string($contentType) || ! is_array($media), UnexpectedValueException::class, 'OpenAPI media entries must be string-keyed arrays.');

        }
    }

    /** @phpstan-assert list<string> $value */
    public static function assertStringList(mixed $value): void
    {
        throw_if(! is_array($value) || ! array_is_list($value), UnexpectedValueException::class, 'Expected a list of strings.');

        foreach ($value as $item) {
            throw_unless(is_string($item), UnexpectedValueException::class, 'Expected a list of strings.');
        }
    }

    /** @phpstan-assert ApiValue8 $value */
    private static function assertValue8(mixed $value): void
    {
        self::assertValue($value, 8);
    }
}
