<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes the `resource` type that has been closed (e.g. a file handle through `fclose()`).
 */
class TClosedResource extends Atomic
{
    public function __toString(): string
    {
        return 'closed-resource';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'closed-resource';
    }

    public function getId(bool $nested = false): string
    {
        return 'closed-resource';
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
