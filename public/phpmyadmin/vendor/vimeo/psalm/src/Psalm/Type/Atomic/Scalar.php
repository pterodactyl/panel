<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

abstract class Scalar extends Atomic
{
    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return true;
    }
}
