<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an array that is _also_ `callable`.
 */
class TCallableArray extends TNonEmptyArray
{
    /**
     * @var string
     */
    public $value = 'callable-array';
}
