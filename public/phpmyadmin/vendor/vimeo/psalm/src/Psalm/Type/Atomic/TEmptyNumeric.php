<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `numeric` type that's also empty (which can also result from an `is_numeric` and `empty` check).
 */
class TEmptyNumeric extends TNumeric
{
    public function getId(bool $nested = false): string
    {
        return 'empty-numeric';
    }
}
