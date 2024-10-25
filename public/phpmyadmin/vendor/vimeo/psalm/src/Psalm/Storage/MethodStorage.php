<?php

namespace Psalm\Storage;

use Psalm\Type\Union;

class MethodStorage extends FunctionLikeStorage
{
    /**
     * @var bool
     */
    public $is_static = false;

    /**
     * @var int
     */
    public $visibility = 0;

    /**
     * @var bool
     */
    public $final = false;

    /**
     * @var bool
     */
    public $final_from_docblock = false;

    /**
     * @var bool
     */
    public $abstract = false;

    /**
     * @var bool
     */
    public $overridden_downstream = false;

    /**
     * @var bool
     */
    public $overridden_somewhere = false;

    /**
     * @var bool
     */
    public $inheritdoc = false;

    /**
     * @var ?bool
     */
    public $inherited_return_type = false;

    /**
     * @var ?string
     */
    public $defining_fqcln;

    /**
     * @var bool
     */
    public $has_docblock_param_types = false;

    /**
     * @var bool
     */
    public $has_docblock_return_type = false;

    /**
     * @var bool
     */
    public $external_mutation_free = false;

    /**
     * @var bool
     */
    public $immutable = false;

    /**
     * @var bool
     */
    public $mutation_free_inferred = false;

    /**
     * @var ?array<string, bool>
     */
    public $this_property_mutations;

    /**
     * @var Union|null
     */
    public $self_out_type;

    /**
     * @var Union|null
     */
    public $if_this_is_type = null;
    /**
     * @var bool
     */
    public $stubbed = false;

    /**
     * @var bool
     */
    public $probably_fluent = false;
}
