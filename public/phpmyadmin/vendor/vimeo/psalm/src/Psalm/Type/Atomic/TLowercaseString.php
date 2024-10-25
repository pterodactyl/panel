<?php

namespace Psalm\Type\Atomic;

class TLowercaseString extends TString
{
    public function getId(bool $nested = false): string
    {
        return 'lowercase-string';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
