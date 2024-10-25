<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a string, that is also non-falsy (every string except '' and '0')
 */
class TNonFalsyString extends TNonEmptyString
{
    public function getId(bool $nested = false): string
    {
        return 'non-falsy-string';
    }
}
