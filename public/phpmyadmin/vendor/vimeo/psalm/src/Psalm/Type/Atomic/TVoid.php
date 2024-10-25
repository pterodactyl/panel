<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes the `void` type, normally just used to annotate a function/method that returns nothing
 */
class TVoid extends Atomic
{
    public function __toString(): string
    {
        return 'void';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'void';
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
        return $php_major_version > 7
            || ($php_major_version === 7 && $php_minor_version >= 1)
            ? $this->getKey() : null;
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return true;
    }
}
