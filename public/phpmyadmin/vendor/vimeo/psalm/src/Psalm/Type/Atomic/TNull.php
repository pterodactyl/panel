<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes the `null` type
 */
class TNull extends Atomic
{
    public function __toString(): string
    {
        return 'null';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'null';
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
