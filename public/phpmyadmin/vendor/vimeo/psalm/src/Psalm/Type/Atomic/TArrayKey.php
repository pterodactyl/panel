<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `array-key` type, used for something that could be the offset of an `array`.
 */
class TArrayKey extends Scalar
{
    public function __toString(): string
    {
        return 'array-key';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'array-key';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        return null;
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return $use_phpdoc_format ? '(int|string)' : 'array-key';
    }
}
