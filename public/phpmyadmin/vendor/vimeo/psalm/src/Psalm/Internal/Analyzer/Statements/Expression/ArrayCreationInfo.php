<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

class ArrayCreationInfo
{
    /**
     * @var list<Atomic>
     */
    public $item_key_atomic_types = [];

    /**
     * @var list<Atomic>
     */
    public $item_value_atomic_types = [];

    /**
     * @var array<int|string, Union>
     */
    public $property_types = [];

    /**
     * @var array<string, true>
     */
    public $class_strings = [];

    /**
     * @var bool
     */
    public $can_create_objectlike = true;

    /**
     * @var array<int|string, true>
     */
    public $array_keys = [];

    /**
     * @var int
     */
    public $int_offset = 0;

    /**
     * @var bool
     */
    public $all_list = true;

    /**
     * @var array<string, DataFlowNode>
     */
    public $parent_taint_nodes = [];
}
