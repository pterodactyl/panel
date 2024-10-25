<?php

namespace Psalm\Internal\Scanner;

use Psalm\Type\Union;

/**
 * @internal
 */
class VarDocblockComment
{
    /**
     * @var ?Union
     */
    public $type;

    /**
     * @var string|null
     */
    public $var_id;

    /**
     * @var int|null
     */
    public $line_number;

    /**
     * @var int|null
     */
    public $type_start;

    /**
     * @var int|null
     */
    public $type_end;

    /**
     * Whether or not the property is deprecated
     *
     * @var bool
     */
    public $deprecated = false;

    /**
     * Whether or not the property is internal
     *
     * @var bool
     */
    public $internal = false;

    /**
     * If set, the property is internal to the given namespace.
     *
     * @var list<non-empty-string>
     */
    public $psalm_internal = [];

    /**
     * Whether or not the property is readonly
     *
     * @var bool
     */
    public $readonly = false;

    /**
     * Whether or not to allow mutation by internal methods
     *
     * @var bool
     */
    public $allow_private_mutation = false;

    /**
     * @var list<string>
     */
    public $removed_taints = [];

    /**
     * @var array<int, string>
     */
    public $suppressed_issues = [];

    /**
     * @var ?string
     */
    public $description;
}
