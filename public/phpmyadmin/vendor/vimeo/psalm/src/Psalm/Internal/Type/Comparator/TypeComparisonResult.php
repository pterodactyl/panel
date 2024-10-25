<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Type\Atomic;
use Psalm\Type\Union;

class TypeComparisonResult
{
    /** @var ?bool */
    public $scalar_type_match_found;

    /** @var ?bool */
    public $type_coerced;

    /** @var ?bool */
    public $type_coerced_from_mixed;

    /** @var ?bool */
    public $type_coerced_from_as_mixed;

    /** @var ?bool */
    public $to_string_cast;

    /**
     * This is primarily used for array access.
     * For example in this function we know that there are only two possible keys, 0 and 1
     * But we allow the array to be addressed by an arbitrary integer $i.
     *
     * function takesAnInt(int $i): string {
     *     return ["foo", "bar"][$i];
     * }
     *
     * @var ?bool
     */
    public $type_coerced_from_scalar;

    /** @var ?Union */
    public $replacement_union_type;

    /** @var ?Atomic */
    public $replacement_atomic_type;
}
