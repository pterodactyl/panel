<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a non-empty-string where every character is lowercased. (which can also result from a `strtolower` call).
 */
class TNonEmptyLowercaseString extends TNonEmptyString
{
    public function getId(bool $nested = false): string
    {
        return 'non-empty-lowercase-string';
    }

    /**
     * @return false
     */
    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
