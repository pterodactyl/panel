<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Represents any value reduced to false when computed in boolean context. This is used for assertions
 */
class TAssertionFalsy extends Atomic
{
    public function __toString(): string
    {
        return 'falsy';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'falsy';
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'falsy';
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
}
