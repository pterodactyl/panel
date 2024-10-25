<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a `scalar` type that is also empty.
 */
class TEmptyScalar extends TScalar
{
    public function getId(bool $nested = false): string
    {
        return 'empty-scalar';
    }
}
