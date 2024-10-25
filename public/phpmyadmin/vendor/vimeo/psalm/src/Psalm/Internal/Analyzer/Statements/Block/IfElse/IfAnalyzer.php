<?php

namespace Psalm\Internal\Analyzer\Statements\Block\IfElse;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Algebra;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Scope\IfConditionalScope;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\ConflictingReferenceConstraint;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\BinaryOp\VirtualBooleanOr;
use Psalm\Node\Expr\VirtualBooleanNot;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Name\VirtualFullyQualified;
use Psalm\Node\VirtualArg;
use Psalm\Type;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_diff_key;
use function array_filter;
use function array_intersect;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_unique;
use function count;
use function in_array;
use function strpos;
use function substr;

class IfAnalyzer
{
    /**
     * @param  array<string, Union> $pre_assignment_else_redefined_vars
     *
     * @return false|null
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\If_ $stmt,
        IfScope $if_scope,
        IfConditionalScope $if_conditional_scope,
        Context $if_context,
        Context $old_if_context,
        Context $outer_context,
        array $pre_assignment_else_redefined_vars
    ): ?bool {
        $codebase = $statements_analyzer->getCodebase();

        $if_context->parent_context = $outer_context;

        $assigned_var_ids = $if_context->assigned_var_ids;
        $possibly_assigned_var_ids = $if_context->possibly_assigned_var_ids;
        $if_context->assigned_var_ids = [];
        $if_context->possibly_assigned_var_ids = [];

        if ($statements_analyzer->analyze(
            $stmt->stmts,
            $if_context
        ) === false
        ) {
            return false;
        }

        $final_actions = ScopeAnalyzer::getControlActions(
            $stmt->stmts,
            $statements_analyzer->node_data,
            $codebase->config->exit_functions,
            []
        );

        $has_ending_statements = $final_actions === [ScopeAnalyzer::ACTION_END];

        $has_leaving_statements = $has_ending_statements
            || (count($final_actions) && !in_array(ScopeAnalyzer::ACTION_NONE, $final_actions, true));

        $has_break_statement = $final_actions === [ScopeAnalyzer::ACTION_BREAK];
        $has_continue_statement = $final_actions === [ScopeAnalyzer::ACTION_CONTINUE];

        $if_scope->final_actions = $final_actions;

        /** @var array<string, int> */
        $new_assigned_var_ids = $if_context->assigned_var_ids;
        /** @var array<string, bool> */
        $new_possibly_assigned_var_ids = $if_context->possibly_assigned_var_ids;

        $if_context->assigned_var_ids = array_merge($assigned_var_ids, $new_assigned_var_ids);
        $if_context->possibly_assigned_var_ids = array_merge(
            $possibly_assigned_var_ids,
            $new_possibly_assigned_var_ids
        );

        foreach ($if_context->byref_constraints as $var_id => $byref_constraint) {
            if (isset($outer_context->byref_constraints[$var_id])
                && $byref_constraint->type
                && ($outer_constraint_type = $outer_context->byref_constraints[$var_id]->type)
                && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $byref_constraint->type,
                    $outer_constraint_type
                )
            ) {
                IssueBuffer::maybeAdd(
                    new ConflictingReferenceConstraint(
                        'There is more than one pass-by-reference constraint on ' . $var_id
                            . ' between ' . $byref_constraint->type->getId()
                            . ' and ' . $outer_constraint_type->getId(),
                        new CodeLocation($statements_analyzer, $stmt, $outer_context->include_location, true)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } else {
                $outer_context->byref_constraints[$var_id] = $byref_constraint;
            }
        }

        $mic_drop = false;

        if (!$has_leaving_statements) {
            self::updateIfScope(
                $codebase,
                $if_scope,
                $if_context,
                $outer_context,
                $new_assigned_var_ids,
                $new_possibly_assigned_var_ids,
                $if_scope->if_cond_changed_var_ids
            );

            if ($if_scope->reasonable_clauses) {
                // remove all reasonable clauses that would be negated by the if stmts
                foreach ($new_assigned_var_ids as $var_id => $_) {
                    $if_scope->reasonable_clauses = Context::filterClauses(
                        $var_id,
                        $if_scope->reasonable_clauses,
                        $if_context->vars_in_scope[$var_id] ?? null,
                        $statements_analyzer
                    );
                }
            }
        } else {
            if (!$has_break_statement) {
                $if_scope->reasonable_clauses = [];

                // If we're assigning inside
                if ($if_conditional_scope->assigned_in_conditional_var_ids
                    && $if_scope->post_leaving_if_context
                ) {
                    self::addConditionallyAssignedVarsToContext(
                        $statements_analyzer,
                        $stmt->cond,
                        $if_scope->post_leaving_if_context,
                        $outer_context,
                        $if_conditional_scope->assigned_in_conditional_var_ids
                    );
                }

                if (!$stmt->else && !$stmt->elseifs) {
                    $mic_drop = self::handleMicDrop(
                        $statements_analyzer,
                        $stmt->cond,
                        $if_scope,
                        $outer_context,
                        $new_assigned_var_ids
                    );

                    $outer_context->clauses = Algebra::simplifyCNF(
                        array_merge($outer_context->clauses, $if_scope->negated_clauses)
                    );
                }
            }
        }

        // update the parent context as necessary, but only if we can safely reason about type negation.
        // We only update vars that changed both at the start of the if block and then again by an assignment
        // in the if statement.
        if ($if_scope->negated_types && !$mic_drop) {
            $vars_to_update = array_intersect(
                array_keys($pre_assignment_else_redefined_vars),
                array_keys($if_scope->negated_types)
            );

            $extra_vars_to_update = [];

            // if there's an object-like array in there, we also need to update the root array variable
            foreach ($vars_to_update as $var_id) {
                $bracked_pos = strpos($var_id, '[');
                if ($bracked_pos !== false) {
                    $extra_vars_to_update[] = substr($var_id, 0, $bracked_pos);
                }
            }

            if ($extra_vars_to_update) {
                $vars_to_update = array_unique(array_merge($extra_vars_to_update, $vars_to_update));
            }

            //update $if_context vars to include the pre-assignment else vars
            if (!$stmt->else && !$has_leaving_statements) {
                foreach ($pre_assignment_else_redefined_vars as $var_id => $type) {
                    if (isset($if_context->vars_in_scope[$var_id])) {
                        $if_context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $if_context->vars_in_scope[$var_id],
                            $type,
                            $codebase
                        );
                    }
                }
            }

            $outer_context->update(
                $old_if_context,
                $if_context,
                $has_leaving_statements,
                $vars_to_update,
                $if_scope->updated_vars
            );
        }

        if (!$has_ending_statements) {
            $vars_possibly_in_scope = array_diff_key(
                $if_context->vars_possibly_in_scope,
                $outer_context->vars_possibly_in_scope
            );

            if ($if_context->loop_scope) {
                if (!$has_continue_statement && !$has_break_statement) {
                    $if_scope->new_vars_possibly_in_scope = $vars_possibly_in_scope;
                }

                $if_context->loop_scope->vars_possibly_in_scope = array_merge(
                    $vars_possibly_in_scope,
                    $if_context->loop_scope->vars_possibly_in_scope
                );
            } elseif (!$has_leaving_statements) {
                $if_scope->new_vars_possibly_in_scope = $vars_possibly_in_scope;
            }
        }

        if ($outer_context->collect_exceptions) {
            $outer_context->mergeExceptions($if_context);
        }

        return null;
    }

    /**
     * This handles the situation when returning inside an
     * if block with no else or elseifs
     *
     * @param array<string, int>    $new_assigned_var_ids
     */
    private static function handleMicDrop(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $cond,
        IfScope $if_scope,
        Context $post_if_context,
        array $new_assigned_var_ids
    ): bool {
        if (!$if_scope->negated_types) {
            return false;
        }

        $newly_reconciled_var_ids = [];

        $post_if_context_vars_reconciled = Reconciler::reconcileKeyedTypes(
            $if_scope->negated_types,
            [],
            $post_if_context->vars_in_scope,
            $newly_reconciled_var_ids,
            [],
            $statements_analyzer,
            $statements_analyzer->getTemplateTypeMap() ?: [],
            $post_if_context->inside_loop,
            new CodeLocation(
                $statements_analyzer->getSource(),
                $cond instanceof PhpParser\Node\Expr\BooleanNot
                    ? $cond->expr
                    : $cond,
                $post_if_context->include_location,
                false
            )
        );

        foreach ($newly_reconciled_var_ids as $changed_var_id => $_) {
            $post_if_context->removeVarFromConflictingClauses($changed_var_id);
        }

        $newly_reconciled_var_ids += $new_assigned_var_ids;

        foreach ($newly_reconciled_var_ids as $var_id => $_) {
            $if_scope->negated_clauses = Context::filterClauses(
                $var_id,
                $if_scope->negated_clauses
            );
        }

        foreach ($newly_reconciled_var_ids as $var_id => $_) {
            $first_appearance = $statements_analyzer->getFirstAppearance($var_id);

            if ($first_appearance
                && isset($post_if_context->vars_in_scope[$var_id])
                && isset($post_if_context_vars_reconciled[$var_id])
                && $post_if_context->vars_in_scope[$var_id]->hasMixed()
                && !$post_if_context_vars_reconciled[$var_id]->hasMixed()
            ) {
                if (!$post_if_context->collect_initializations
                    && !$post_if_context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                ) {
                    $parent_source = $statements_analyzer->getSource();

                    $functionlike_storage = $parent_source instanceof FunctionLikeAnalyzer
                        ? $parent_source->getFunctionLikeStorage($statements_analyzer)
                        : null;

                    if (!$functionlike_storage
                            || (!$parent_source->getSource() instanceof TraitAnalyzer
                                && !isset($functionlike_storage->param_lookup[substr($var_id, 1)]))
                    ) {
                        $codebase = $statements_analyzer->getCodebase();
                        $codebase->analyzer->decrementMixedCount($statements_analyzer->getFilePath());
                    }
                }

                IssueBuffer::remove(
                    $statements_analyzer->getFilePath(),
                    'MixedAssignment',
                    $first_appearance->raw_file_start
                );
            }
        }

        $post_if_context->vars_in_scope = $post_if_context_vars_reconciled;

        return true;
    }

    /**
     * @param array<string, int> $assigned_in_conditional_var_ids
     */
    public static function addConditionallyAssignedVarsToContext(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $cond,
        Context $post_leaving_if_context,
        Context $post_if_context,
        array $assigned_in_conditional_var_ids
    ): void {
        // this filters out coercions to expected types in ArgumentAnalyzer
        $assigned_in_conditional_var_ids = array_filter($assigned_in_conditional_var_ids);

        if (!$assigned_in_conditional_var_ids) {
            return;
        }

        $exprs = self::getDefinitelyEvaluatedOredExpressions($cond);

        // if there was no assignment in the first expression it's safe to proceed
        $old_node_data = $statements_analyzer->node_data;
        $statements_analyzer->node_data = clone $old_node_data;

        IssueBuffer::startRecording();

        foreach ($exprs as $expr) {
            if ($expr instanceof PhpParser\Node\Expr\BinaryOp\BooleanAnd) {
                $fake_not = new VirtualBooleanOr(
                    self::negateExpr($expr->left),
                    self::negateExpr($expr->right),
                    $expr->getAttributes()
                );
            } else {
                $fake_not = self::negateExpr($expr);
            }

            $fake_negated_expr = new VirtualFuncCall(
                new VirtualFullyQualified('assert'),
                [new VirtualArg(
                    $fake_not,
                    false,
                    false,
                    $expr->getAttributes()
                )],
                $expr->getAttributes()
            );

            $post_leaving_if_context->inside_negation = !$post_leaving_if_context->inside_negation;

            ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $fake_negated_expr,
                $post_leaving_if_context
            );

            $post_leaving_if_context->inside_negation = !$post_leaving_if_context->inside_negation;
        }

        IssueBuffer::clearRecordingLevel();
        IssueBuffer::stopRecording();

        $statements_analyzer->node_data = $old_node_data;

        foreach ($assigned_in_conditional_var_ids as $var_id => $_) {
            if (isset($post_leaving_if_context->vars_in_scope[$var_id])) {
                $post_if_context->vars_in_scope[$var_id] = clone $post_leaving_if_context->vars_in_scope[$var_id];
            }
        }
    }

    /**
     * Returns all expressions inside an ored expression
     * @return non-empty-list<PhpParser\Node\Expr>
     */
    private static function getDefinitelyEvaluatedOredExpressions(PhpParser\Node\Expr $stmt): array
    {
        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr
            || $stmt instanceof PhpParser\Node\Expr\BinaryOp\LogicalXor
        ) {
            return array_merge(
                self::getDefinitelyEvaluatedOredExpressions($stmt->left),
                self::getDefinitelyEvaluatedOredExpressions($stmt->right)
            );
        }

        return [$stmt];
    }

    private static function negateExpr(PhpParser\Node\Expr $expr): PhpParser\Node\Expr
    {
        if ($expr instanceof PhpParser\Node\Expr\BooleanNot) {
            return $expr->expr;
        }

        return new VirtualBooleanNot($expr, $expr->getAttributes());
    }

    /**
     * @param  array<string, int>    $assigned_var_ids
     * @param  array<string, bool>   $possibly_assigned_var_ids
     * @param  array<string, bool>   $newly_reconciled_var_ids
     */
    public static function updateIfScope(
        Codebase $codebase,
        IfScope $if_scope,
        Context $if_context,
        Context $outer_context,
        array $assigned_var_ids,
        array $possibly_assigned_var_ids,
        array $newly_reconciled_var_ids,
        bool $update_new_vars = true
    ): void {
        $redefined_vars = $if_context->getRedefinedVars($outer_context->vars_in_scope);

        if ($if_scope->new_vars === null) {
            if ($update_new_vars) {
                $if_scope->new_vars = array_diff_key($if_context->vars_in_scope, $outer_context->vars_in_scope);
            }
        } else {
            foreach ($if_scope->new_vars as $new_var => $type) {
                if (!$if_context->hasVariable($new_var)) {
                    unset($if_scope->new_vars[$new_var]);
                } else {
                    $if_scope->new_vars[$new_var] = Type::combineUnionTypes(
                        $type,
                        $if_context->vars_in_scope[$new_var],
                        $codebase
                    );
                }
            }
        }

        $possibly_redefined_vars = $redefined_vars;

        foreach ($possibly_redefined_vars as $var_id => $_) {
            if (!isset($possibly_assigned_var_ids[$var_id])
                && isset($newly_reconciled_var_ids[$var_id])
            ) {
                unset($possibly_redefined_vars[$var_id]);
            }
        }

        if ($if_scope->assigned_var_ids === null) {
            $if_scope->assigned_var_ids = $assigned_var_ids;
        } else {
            $if_scope->assigned_var_ids = array_intersect_key($assigned_var_ids, $if_scope->assigned_var_ids);
        }

        $if_scope->possibly_assigned_var_ids += $possibly_assigned_var_ids;

        if ($if_scope->redefined_vars === null) {
            $if_scope->redefined_vars = $redefined_vars;
            $if_scope->possibly_redefined_vars = $possibly_redefined_vars;
        } else {
            foreach ($if_scope->redefined_vars as $redefined_var => $type) {
                if (!isset($redefined_vars[$redefined_var])) {
                    unset($if_scope->redefined_vars[$redefined_var]);
                } else {
                    $if_scope->redefined_vars[$redefined_var] = Type::combineUnionTypes(
                        $redefined_vars[$redefined_var],
                        $type,
                        $codebase
                    );

                    if (isset($outer_context->vars_in_scope[$redefined_var])
                        && $if_scope->redefined_vars[$redefined_var]->equals(
                            $outer_context->vars_in_scope[$redefined_var]
                        )
                    ) {
                        unset($if_scope->redefined_vars[$redefined_var]);
                    }
                }
            }

            foreach ($possibly_redefined_vars as $var => $type) {
                $if_scope->possibly_redefined_vars[$var] = Type::combineUnionTypes(
                    $type,
                    $if_scope->possibly_redefined_vars[$var] ?? null,
                    $codebase
                );
            }
        }
    }
}
