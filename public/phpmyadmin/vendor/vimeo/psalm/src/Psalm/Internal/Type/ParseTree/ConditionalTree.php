<?php

namespace Psalm\Internal\Type\ParseTree;

use Psalm\Internal\Type\ParseTree;

/**
 * @internal
 */
class ConditionalTree extends ParseTree
{
    /**
     * @var TemplateIsTree
     */
    public $condition;

    public function __construct(TemplateIsTree $condition, ?ParseTree $parent = null)
    {
        $this->condition = $condition;
        $this->parent = $parent;
    }
}
