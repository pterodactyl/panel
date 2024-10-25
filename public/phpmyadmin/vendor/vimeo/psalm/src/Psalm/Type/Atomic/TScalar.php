<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `scalar` super type (which can also result from an `is_scalar` check).
 * This type encompasses `float`, `int`, `bool` and `string`.
 */
class TScalar extends Scalar
{
    public function __toString(): string
    {
        return 'scalar';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'scalar';
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

    public function getAssertionString(bool $exact = false): string
    {
        return 'scalar';
    }
}
