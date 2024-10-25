<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `true` value type
 */
class TTrue extends TBool
{
    /** @var true */
    public $value = true;

    public function __toString(): string
    {
        return 'true';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'true';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
