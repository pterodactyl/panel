<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes array known to be non-empty of the form `non-empty-array<TKey, TValue>`.
 * It expects an array with two elements, both union types.
 */
class TNonEmptyArray extends TArray
{
    /**
     * @var int|null
     */
    public $count;

    /**
     * @var string
     */
    public $value = 'non-empty-array';
}
