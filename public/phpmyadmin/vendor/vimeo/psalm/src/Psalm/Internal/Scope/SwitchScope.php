<?php

namespace Psalm\Internal\Scope;

use PhpParser;
use Psalm\Internal\Clause;
use Psalm\Type\Union;

/**
 * @internal
 */
class SwitchScope
{
    /**
     * @var array<string, Union>|null
     */
    public $new_vars_in_scope;

    /**
     * @var array<string, bool>
     */
    public $new_vars_possibly_in_scope = [];

    /**
     * @var array<string, Union>|null
     */
    public $redefined_vars;

    /**
     * @var array<string, Union>|null
     */
    public $possibly_redefined_vars;

    /**
     * @var array<PhpParser\Node\Stmt>
     */
    public $leftover_statements = [];

    /**
     * @var PhpParser\Node\Expr|null
     */
    public $leftover_case_equality_expr;

    /**
     * @var list<Clause>
     */
    public $negated_clauses = [];

    /**
     * @var array<string, bool>|null
     */
    public $new_assigned_var_ids;
}
