<?php

namespace Psalm\Internal\Scope;

use Psalm\Context;
use Psalm\Internal\Clause;

/**
 * @internal
 */
class IfConditionalScope
{
    /** @var Context */
    public $if_context;

    /** @var Context */
    public $post_if_context;

    /**
     * @var array<string, bool>
     */
    public $cond_referenced_var_ids;

    /**
     * @var array<string, int>
     */
    public $assigned_in_conditional_var_ids;

    /** @var list<Clause> */
    public $entry_clauses;

    /**
     * @param array<string, bool>   $cond_referenced_var_ids
     * @param array<string, int>   $assigned_in_conditional_var_ids
     * @param list<Clause> $entry_clauses
     */
    public function __construct(
        Context $if_context,
        Context $post_if_context,
        array $cond_referenced_var_ids,
        array $assigned_in_conditional_var_ids,
        array $entry_clauses
    ) {
        $this->if_context = $if_context;
        $this->post_if_context = $post_if_context;
        $this->cond_referenced_var_ids = $cond_referenced_var_ids;
        $this->assigned_in_conditional_var_ids = $assigned_in_conditional_var_ids;
        $this->entry_clauses = $entry_clauses;
    }
}
