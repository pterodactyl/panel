<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an object-like array that is _also_ `callable`.
 */
class TCallableKeyedArray extends TKeyedArray
{
    public const KEY = 'callable-array';

    public function getKey(bool $include_extra = true): string
    {
        return 'array';
    }
}
