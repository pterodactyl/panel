<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a string that's also a numeric value e.g. `"5"`. It can result from `is_string($s) && is_numeric($s)`.
 */
class TNumericString extends TNonEmptyString
{
    public function getKey(bool $include_extra = true): string
    {
        return 'numeric-string';
    }

    public function __toString(): string
    {
        return 'numeric-string';
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
