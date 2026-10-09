<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Extensions;

use InvalidArgumentException;

/**
 * Extension field values a request authorized and validated against every extension's rules
 * (ValidatesExtensionFields). Actions only save values of this type, so input that skipped
 * those checks, such as a raw `extensions` array, is refused instead of stored.
 */
final readonly class ValidatedExtensionValues
{
    /** @param ExtensionFieldInput $values */
    private function __construct(private array $values) {}

    /**
     * @internal built by ExtensionFields::validate() from values that passed every check
     *
     * @param  ExtensionFieldInput  $values
     */
    public static function fromValidation(array $values): self
    {
        return new self($values);
    }

    public static function none(): self
    {
        return new self([]);
    }

    /**
     * The values an action received in its data's `extensions` entry: none when it has no
     * such entry. Anything other than validated values is refused.
     *
     * @phpstan-assert self|null $value
     */
    public static function of(mixed $value): self
    {
        throw_unless($value === null || $value instanceof self, InvalidArgumentException::class, 'Extension field values must come from a request that validated them.');

        return $value ?? self::none();
    }

    /** @return ExtensionFieldInput */
    public function all(): array
    {
        return $this->values;
    }
}
