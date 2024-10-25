<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class CallableParamTree extends ParseTree
{
    /**
     * @var bool
     */
    public $variadic = false;

    /**
     * @var bool
     */
    public $has_default = false;
}
