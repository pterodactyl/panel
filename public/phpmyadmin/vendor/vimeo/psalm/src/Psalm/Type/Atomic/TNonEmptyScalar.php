<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a `scalar` type that is also non-empty.
 */
class TNonEmptyScalar extends TScalar
{
    public function getId(bool $nested = false): string
    {
        return 'non-empty-scalar';
    }
}
