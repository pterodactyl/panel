<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class MethodParamTree extends ParseTree
{
    /**
     * @var bool
     */
    public $variadic;

    /**
     * @var string
     */
    public $default = '';

    /**
     * @var bool
     */
    public $byref;

    /**
     * @var string
     */
    public $name;

    public function __construct(
        string $name,
        bool $byref,
        bool $variadic,
        ?ParseTree $parent = null
    ) {
        $this->name = $name;
        $this->byref = $byref;
        $this->variadic = $variadic;
        $this->parent = $parent;
    }
}
