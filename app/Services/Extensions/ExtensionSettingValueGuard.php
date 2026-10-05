<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use Illuminate\Support\Facades\Crypt;
use Pterodactyl\Support\JsonValueGuard;
use UnexpectedValueException;

final class ExtensionSettingValueGuard
{
    /**
     * Hex with optional alpha, or a numeric oklch() triple with optional alpha.
     */
    private const string COLOR_PATTERN = '/\A(?:#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})|oklch\((?&n)%? (?&n)%? (?&n)(?:deg)?(?: \/ (?&n)%?)?\))\z(?(DEFINE)(?<n>\d{1,3}(?:\.\d{1,6})?|\.\d{1,6}))/';

    /**
     * @return ExtensionSettingValue
     */
    public static function decode(string $encoded, bool $secret = false): bool|float|int|string|array|null
    {
        $value = JsonValueGuard::decode($encoded);
        if (! $secret) {
            return $value;
        }

        throw_unless(is_string($value), UnexpectedValueException::class, 'Encrypted extension settings must be strings.');

        return JsonValueGuard::decode(Crypt::decryptString($value));
    }

    /** @param ExtensionSettingValue $value */
    public static function encode(mixed $value, bool $secret = false): string
    {
        $encoded = json_encode($value, JSON_THROW_ON_ERROR);

        return $secret ? json_encode(Crypt::encryptString($encoded), JSON_THROW_ON_ERROR) : $encoded;
    }

    /**
     * @phpstan-assert ExtensionSettingValue $value
     */
    public static function assertValue(mixed $value, int $remainingDepth = 10): void
    {
        JsonValueGuard::assertValue($value, $remainingDepth);
    }

    /** @phpstan-assert ExtensionSettingValues $value */
    public static function assertValues(mixed $value): void
    {
        throw_unless(is_array($value), UnexpectedValueException::class, 'Extension settings must be an object.');

        foreach ($value as $key => $item) {
            throw_unless(is_string($key), UnexpectedValueException::class, 'Extension setting names must be strings.');

            self::assertValue($item);
        }
    }

    /**
     * The canonical form of a colour setting (lower case, single spaces), or
     * null when the value is not a colour this panel accepts.
     *
     * @param  ExtensionSettingValue  $value
     */
    public static function color(bool|float|int|string|array|null $value): ?string
    {
        if (! is_string($value) || mb_strlen($value) > 64) {
            return null;
        }

        $color = preg_replace(['/\s+/', '/ ?\/ ?/'], [' ', ' / '], mb_strtolower(mb_trim($value))) ?? '';
        $color = str_replace(['( ', ' )'], ['(', ')'], $color);

        return preg_match(self::COLOR_PATTERN, $color) === 1 ? $color : null;
    }

    /**
     * A multi-line text setting with its line endings normalized, or null when
     * the value is not a string.
     *
     * @param  ExtensionSettingValue  $value
     */
    public static function text(bool|float|int|string|array|null $value): ?string
    {
        return is_string($value) ? str_replace(["\r\n", "\r"], "\n", $value) : null;
    }

    /**
     * The string items of a list setting, in order, or null when the value is
     * not a list.
     *
     * @param  ExtensionSettingValue  $value
     * @return list<string>|null
     */
    public static function strings(bool|float|int|string|array|null $value): ?array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            return null;
        }

        return array_values(array_filter($value, is_string(...)));
    }

    /**
     * The declared choices present in a multiselect value, deduplicated and in
     * declaration order, or null when the value is not a list. Anything that is
     * not a declared choice is dropped.
     *
     * @param  ExtensionSettingValue  $value
     * @param  list<string|int>  $choices
     * @return list<string|int>|null
     */
    public static function choices(bool|float|int|string|array|null $value, array $choices): ?array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            return null;
        }

        $selected = [];
        foreach ($value as $item) {
            if (is_string($item) || is_int($item)) {
                // SAFETY: only strings and integers reach this cast, and both stringify losslessly.
                $selected[(string) $item] = true;
            }
        }

        // SAFETY: declared choices are strings or integers, which stringify losslessly.
        return array_values(array_filter($choices, fn (string|int $choice): bool => isset($selected[(string) $choice])));
    }

    /**
     * The stored name of an uploaded settings file, or null when the value is not one.
     *
     * @param  ExtensionSettingValue  $value
     */
    public static function fileReference(bool|float|int|string|array|null $value): ?string
    {
        return is_string($value) && preg_match(ExtensionSettingFiles::NAME_PATTERN, $value) === 1 ? $value : null;
    }

    /**
     * @param  ExtensionSettingValue  $value
     * @param  'string'|'number'|'boolean'|'array'|'object'|'null'|'json'  $type
     */
    public static function assertFrontendType(mixed $value, string $type): void
    {
        $valid = match ($type) {
            'string' => is_string($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value) && array_is_list($value),
            'object' => is_array($value) && $value !== [] && array_all(array_keys($value), fn (int|string $key): bool => is_string($key)),
            'null' => $value === null,
            'json' => true,
        };
        throw_unless($valid, UnexpectedValueException::class, "Extension frontend setting does not match its declared {$type} type.");
    }
}
