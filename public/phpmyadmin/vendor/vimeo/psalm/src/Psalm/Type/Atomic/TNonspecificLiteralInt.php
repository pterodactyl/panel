<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `literal-int` type, where the exact value is unknown but
 * we know that the int is not from user input
 */
class TNonspecificLiteralInt extends TInt
{
    public function __toString(): string
    {
        return 'literal-int';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'int';
    }
}
