<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class EncapsulationTree extends ParseTree
{
    /**
     * @var bool
     */
    public $terminated = false;
}
