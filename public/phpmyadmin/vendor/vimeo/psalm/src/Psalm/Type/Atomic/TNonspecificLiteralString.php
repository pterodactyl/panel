<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `literal-string` type, where the exact value is unknown but
 * we know that the string is not from user input
 */
class TNonspecificLiteralString extends TString
{
    public function __toString(): string
    {
        return 'literal-string';
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
