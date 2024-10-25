<?php

namespace Psalm\Internal;

use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

/**
 * @internal
 */
class ReferenceConstraint
{
    /** @var Union|null */
    public $type;

    public function __construct(?Union $type = null)
    {
        if ($type) {
            $this->type = clone $type;

            if ($this->type->getLiteralStrings()) {
                $this->type->addType(new TString);
            }

            if ($this->type->getLiteralInts()) {
                $this->type->addType(new TInt);
            }

            if ($this->type->getLiteralFloats()) {
                $this->type->addType(new TFloat);
            }
        }
    }
}
