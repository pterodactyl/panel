<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class KeyedArrayPropertyTree extends ParseTree
{
    /**
     * @var string
     */
    public $value;

    public function __construct(string $value, ?ParseTree $parent = null)
    {
        $this->value = $value;
        $this->parent = $parent;
    }
}
