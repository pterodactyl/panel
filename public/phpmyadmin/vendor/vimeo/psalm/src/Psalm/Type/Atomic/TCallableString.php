<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `callable-string` type, used to represent an unknown string that is also `callable`.
 */
class TCallableString extends TNonEmptyString
{

    public function getKey(bool $include_extra = true): string
    {
        return 'callable-string';
    }

    public function getId(bool $nested = false): string
    {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'string';
    }
}
