<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class KeyedArrayTree extends ParseTree
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var bool
     */
    public $terminated = false;

    public function __construct(string $value, ?ParseTree $parent = null)
    {
        $this->value = $value;
        $this->parent = $parent;
    }
}
