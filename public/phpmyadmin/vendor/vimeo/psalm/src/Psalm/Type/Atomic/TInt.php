<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `int` type, where the exact value is unknown.
 */
class TInt extends Scalar
{
    public function __toString(): string
    {
        return 'int';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int';
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
        return $php_major_version >= 7 ? 'int' : null;
    }
}
