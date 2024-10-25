<?php

namespace Psalm\Internal\Type;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

/**
 * @internal
 */
class TypeCombination
{
    /** @var array<string, Atomic> */
    public $value_types = [];

    /** @var array<string, TNamedObject>|null */
    public $named_object_types = [];

    /** @var list<Union> */
    public $array_type_params = [];

    /** @var array<string, non-empty-list<Union>> */
    public $builtin_type_params = [];

    /** @var array<string, non-empty-list<Union>> */
    public $object_type_params = [];

    /** @var array<string, bool> */
    public $object_static = [];

    /** @var array<int, bool>|null */
    public $array_counts = [];

    /** @var bool */
    public $array_sometimes_filled = false;

    /** @var bool */
    public $array_always_filled = true;

    /** @var array<string|int, Union> */
    public $objectlike_entries = [];

    /** @var bool */
    public $objectlike_sealed = true;

    /** @var ?Union */
    public $objectlike_key_type;

    /** @var ?Union */
    public $objectlike_value_type;

    /** @var bool */
    public $empty_mixed = false;

    /** @var bool */
    public $non_empty_mixed = false;

    /** @var ?bool */
    public $mixed_from_loop_isset;

    /** @var array<string, TLiteralString>|null */
    public $strings = [];

    /** @var array<string, TLiteralInt>|null */
    public $ints = [];

    /** @var array<string, TLiteralFloat>|null */
    public $floats = [];

    /** @var array<string, TNamedObject|TObject>|null */
    public $class_string_types = [];

    /**
     * @var array<string, TNamedObject|TTemplateParam|TIterable|TObject>|null
     */
    public $extra_types;

    /** @var ?bool */
    public $all_arrays_lists;

    /** @var ?bool */
    public $all_arrays_callable;

    /** @var ?bool */
    public $all_arrays_class_string_maps;

    /** @var array<string, bool> */
    public $class_string_map_names = [];

    /** @var array<string, ?TNamedObject> */
    public $class_string_map_as_types = [];
}
