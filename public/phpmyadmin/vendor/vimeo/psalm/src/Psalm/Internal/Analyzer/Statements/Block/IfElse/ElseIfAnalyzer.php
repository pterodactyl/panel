<?php

namespace Psalm\Internal\Analyzer\Statements\Block\IfElse;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Exception\ComplicatedExpressionException;
use Psalm\Exception\ScopeAnalysisException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfConditionalAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\ConflictingReferenceConstraint;
use Psalm\IssueBuffer;
use Psalm\Type\Reconciler;

use function array_combine;
use function array_diff;
use function array_diff_key;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function preg_match;
use function preg_quote;
use function spl_object_id;

class ElseIfAnalyzer
{
    /**
     * @return false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\ElseIf_ $elseif,
        IfScope $if_scope,
        Context $else_context,
        Context $outer_context,
        Codebase $codebase,
        int $branch_point
    ): ?bool {
        $pre_conditional_context = clone $else_context;

        try {
            $if_conditional_scope = IfConditionalAnalyzer::analyze(
                $statements_analyzer,
                $elseif->cond,
                $else_context,
                $codebase,
                $if_scope,
                $branch_point
            );

            $elseif_context = $if_conditional_scope->if_context;
            $cond_referenced_var_ids = $if_conditional_scope->cond_referenced_var_ids;
            $assigned_in_conditional_var_ids = $if_conditional_scope->assigned_in_conditional_var_ids;
            $entry_clauses = $if_conditional_scope->entry_clauses;
        } catch (ScopeAnalysisException $e) {
            return false;
        }

        $mixed_var_ids = [];

        foreach ($elseif_context->vars_in_scope as $var_id => $type) {
            if ($type->hasMixed()) {
                $mixed_var_ids[] = $var_id;
            }
        }

        $elseif_cond_id = spl_object_id($elseif->cond);

        $elseif_clauses = FormulaGenerator::getFormula(
            $elseif_cond_id,
            $elseif_cond_id,
            $elseif->cond,
            $else_context->self,
            $statements_analyzer,
            $codebase
        );

        $elseif_clauses = array_map(
            /**
             * @return Clause
             */
            function (Clause $c) use ($mixed_var_ids, $elseif_cond_id): Clause {
                $keys = array_keys($c->possibilities);

                $mixed_var_ids = array_diff($mixed_var_ids, $keys);

                foreach ($keys as $key) {
                    foreach ($mixed_var_ids as $mixed_var_id) {
                        if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                            return new Clause([], $elseif_cond_id, $elseif_cond_id, true);
                        }
                    }
                }

                return $c;
            },
            $elseif_clauses
        );

        $entry_clauses = array_map(
            /**
             * @return Clause
             */
            function (Clause $c) use ($assigned_in_conditional_var_ids, $elseif_cond_id): Clause {
                $keys = array_keys($c->possibilities);

                foreach ($keys as $key) {
                    foreach ($assigned_in_conditional_var_ids as $conditional_assigned_var_id => $_) {
                        if (preg_match('/^' . preg_quote($conditional_assigned_var_id, '/') . '(\[|-|$)/', $key)) {
                            return new Clause([], $elseif_cond_id, $elseif_cond_id, true);
                        }
                    }
                }

                return $c;
            },
            $entry_clauses
        );

        // this will see whether any of the clauses in set A conflict with the clauses in set B
        AlgebraAnalyzer::checkForParadox(
            $entry_clauses,
            $elseif_clauses,
            $statements_analyzer,
            $elseif->cond,
            $assigned_in_conditional_var_ids
        );

        $elseif_context_clauses = array_merge($entry_clauses, $elseif_clauses);

        if ($elseif_context->reconciled_expression_clauses) {
            $reconciled_expression_clauses = $elseif_context->reconciled_expression_clauses;

            $elseif_context_clauses = array_values(
                array_filter(
                    $elseif_context_clauses,
                    function ($c) use ($reconciled_expression_clauses): bool {
                        return !in_array($c->hash, $reconciled_expression_clauses);
                    }
                )
            );
        }

        $elseif_context->clauses = Algebra::simplifyCNF($elseif_context_clauses);

        $active_elseif_types = [];

        try {
            if (array_filter(
                $entry_clauses,
                function ($clause): bool {
                    return (bool)$clause->possibilities;
                }
            )) {
                $omit_keys = array_reduce(
                    $entry_clauses,
                    /**
                     * @param array<string> $carry
                     * @return array<string>
                     */
                    function (array $carry, Clause $clause): array {
                        return array_merge($carry, array_keys($clause->possibilities));
                    },
                    []
                );

                $omit_keys = array_combine($omit_keys, $omit_keys);
                $omit_keys = array_diff_key($omit_keys, Algebra::getTruthsFromFormula($entry_clauses));

                $cond_referenced_var_ids = array_diff_key(
                    $cond_referenced_var_ids,
                    $omit_keys
                );
            }
            $reconcilable_elseif_types = Algebra::getTruthsFromFormula(
                $elseif_context->clauses,
                spl_object_id($elseif->cond),
                $cond_referenced_var_ids,
                $active_elseif_types
            );
            $negated_elseif_types = Algebra::getTruthsFromFormula(
                Algebra::negateFormula($elseif_clauses)
            );
        } catch (ComplicatedExpressionException $e) {
            $reconcilable_elseif_types = [];
            $negated_elseif_types = [];
        }

        $all_negated_vars = array_unique(
            array_merge(
                array_keys($negated_elseif_types),
                array_keys($if_scope->negated_types)
            )
        );

        foreach ($all_negated_vars as $var_id) {
            if (isset($negated_elseif_types[$var_id])) {
                if (isset($if_scope->negated_types[$var_id])) {
                    $if_scope->negated_types[$var_id] = array_merge(
                        $if_scope->negated_types[$var_id],
                        $negated_elseif_types[$var_id]
                    );
                } else {
                    $if_scope->negated_types[$var_id] = $negated_elseif_types[$var_id];
                }
            }
        }

        $newly_reconciled_var_ids = [];

        // if the elseif has an || in the conditional, we cannot easily reason about it
        if ($reconcilable_elseif_types) {
            $elseif_vars_reconciled = Reconciler::reconcileKeyedTypes(
                $reconcilable_elseif_types,
                $active_elseif_types,
                $elseif_context->vars_in_scope,
                $newly_reconciled_var_ids,
                $cond_referenced_var_ids,
                $statements_analyzer,
                $statements_analyzer->getTemplateTypeMap() ?: [],
                $elseif_context->inside_loop,
                new CodeLocation(
                    $statements_analyzer->getSource(),
                    $elseif->cond instanceof PhpParser\Node\Expr\BooleanNot
                        ? $elseif->cond->expr
                        : $elseif->cond,
                    $outer_context->include_location
                )
            );

            $elseif_context->vars_in_scope = $elseif_vars_reconciled;

            if ($newly_reconciled_var_ids) {
                $elseif_context->clauses = Context::removeReconciledClauses(
                    $elseif_context->clauses,
                    $newly_reconciled_var_ids
                )[0];

                foreach ($newly_reconciled_var_ids as $changed_var_id => $_) {
                    foreach ($elseif_context->vars_in_scope as $var_id => $_) {
                        if (preg_match('/' . preg_quote($changed_var_id, '/') . '[\]\[\-]/', $var_id)
                            && !array_key_exists($var_id, $newly_reconciled_var_ids)
                            && !array_key_exists($var_id, $cond_referenced_var_ids)
                        ) {
                            unset($elseif_context->vars_in_scope[$var_id]);
                        }
                    }
                }
            }
        }

        $pre_stmts_assigned_var_ids = $elseif_context->assigned_var_ids;
        $elseif_context->assigned_var_ids = [];
        $pre_stmts_possibly_assigned_var_ids = $elseif_context->possibly_assigned_var_ids;
        $elseif_context->possibly_assigned_var_ids = [];

        if ($statements_analyzer->analyze(
            $elseif->stmts,
            $elseif_context
        ) === false
        ) {
            return false;
        }

        /** @var array<string, int> */
        $new_stmts_assigned_var_ids = $elseif_context->assigned_var_ids;
        $elseif_context->assigned_var_ids = $pre_stmts_assigned_var_ids + $new_stmts_assigned_var_ids;

        /** @var array<string, bool> */
        $new_stmts_possibly_assigned_var_ids = $elseif_context->possibly_assigned_var_ids;
        $elseif_context->possibly_assigned_var_ids =
            $pre_stmts_possibly_assigned_var_ids + $new_stmts_possibly_assigned_var_ids;

        foreach ($elseif_context->byref_constraints as $var_id => $byref_constraint) {
            if (isset($outer_context->byref_constraints[$var_id])
                && ($outer_constraint_type = $outer_context->byref_constraints[$var_id]->type)
                && $byref_constraint->type
                && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $byref_constraint->type,
                    $outer_constraint_type
                )
            ) {
                IssueBuffer::maybeAdd(
                    new ConflictingReferenceConstraint(
                        'There is more than one pass-by-reference constraint on ' . $var_id,
                        new CodeLocation($statements_analyzer, $elseif, $outer_context->include_location, true)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } else {
                $outer_context->byref_constraints[$var_id] = $byref_constraint;
            }
        }

        $final_actions = ScopeAnalyzer::getControlActions(
            $elseif->stmts,
            $statements_analyzer->node_data,
            $codebase->config->exit_functions,
            []
        );
        // has a return/throw at end
        $has_ending_statements = $final_actions === [ScopeAnalyzer::ACTION_END];
        $has_leaving_statements = $has_ending_statements
            || (count($final_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $final_actions, true));

        $has_break_statement = $final_actions === [ScopeAnalyzer::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeAnalyzer::ACTION_CONTINUE];

        $if_scope->final_actions = array_merge($final_actions, $if_scope->final_actions);

        // update the parent context as necessary
        if (!$has_leaving_statements) {
            IfAnalyzer::updateIfScope(
                $codebase,
                $if_scope,
                $elseif_context,
                $outer_context,
                array_merge($new_stmts_assigned_var_ids, $assigned_in_conditional_var_ids),
                $new_stmts_possibly_assigned_var_ids,
                $newly_reconciled_var_ids
            );

            $reasonable_clause_count = count($if_scope->reasonable_clauses);

            if ($reasonable_clause_count && $reasonable_clause_count < 20000 && $elseif_clauses) {
                $if_scope->reasonable_clauses = Algebra::combineOredClauses(
                    $if_scope->reasonable_clauses,
                    $elseif_clauses,
                    $elseif_cond_id
                );
            } else {
                $if_scope->reasonable_clauses = [];
            }
        } else {
            $if_scope->reasonable_clauses = [];
        }

        if ($negated_elseif_types) {
            if ($has_leaving_statements) {
                $newly_reconciled_var_ids = [];

                $leaving_vars_reconciled = Reconciler::reconcileKeyedTypes(
                    $negated_elseif_types,
                    [],
                    $pre_conditional_context->vars_in_scope,
                    $newly_reconciled_var_ids,
                    [],
                    $statements_analyzer,
                    $statements_analyzer->getTemplateTypeMap() ?: [],
                    $elseif_context->inside_loop,
                    new CodeLocation($statements_analyzer->getSource(), $elseif, $outer_context->include_location)
                );

                $implied_outer_context = clone $elseif_context;
                $implied_outer_context->vars_in_scope = $leaving_vars_reconciled;

                $updated_vars = [];

                $outer_context->update(
                    $elseif_context,
                    $implied_outer_context,
                    false,
                    array_keys($negated_elseif_types),
                    $updated_vars
                );
            }
        }

        if (!$has_ending_statements) {
            $vars_possibly_in_scope = array_diff_key(
                $elseif_context->vars_possibly_in_scope,
                $outer_context->vars_possibly_in_scope
            );

            $possibly_assigned_var_ids = $new_stmts_possibly_assigned_var_ids;

            if ($has_leaving_statements && $elseif_context->loop_scope) {
                if (!$has_continue_statement && !$has_break_statement) {
                    $if_scope->new_vars_possibly_in_scope = array_merge(
                        $vars_possibly_in_scope,
                        $if_scope->new_vars_possibly_in_scope
                    );
                    $if_scope->possibly_assigned_var_ids = array_merge(
                        $possibly_assigned_var_ids,
                        $if_scope->possibly_assigned_var_ids
                    );
                }

                $elseif_context->loop_scope->vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $elseif_context->loop_scope->vars_possibly_in_scope
                );
            } elseif (!$has_leaving_statements) {
                $if_scope->new_vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $if_scope->new_vars_possibly_in_scope
                );
                $if_scope->possibly_assigned_var_ids = array_merge(
                    $possibly_assigned_var_ids,
                    $if_scope->possibly_assigned_var_ids
                );
            }
        }

        if ($outer_context->collect_exceptions) {
            $outer_context->mergeExceptions($elseif_context);
        }

        try {
            $if_scope->negated_clauses = Algebra::simplifyCNF(
                array_merge(
                    $if_scope->negated_clauses,
                    Algebra::negateFormula($elseif_clauses)
                )
            );
        } catch (ComplicatedExpressionException $e) {
            $if_scope->negated_clauses = [];
        }

        return null;
    }
}
