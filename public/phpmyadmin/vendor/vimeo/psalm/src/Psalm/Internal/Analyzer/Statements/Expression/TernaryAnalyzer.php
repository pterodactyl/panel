<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Exception\ScopeAnalysisException;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\AlgebraAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\IfConditionalAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Clause;
use Psalm\Internal\Scope\IfScope;
use Psalm\Internal\Type\AssertionReconciler;
use Psalm\Type;
use Psalm\Type\Reconciler;

use function array_diff;
use function array_filter;
use function array_intersect;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function in_array;
use function preg_match;
use function preg_quote;
use function spl_object_id;

/**
 * @internal
 */
class TernaryAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Ternary $stmt,
        Context $context
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        $if_scope = new IfScope();

        try {
            $if_conditional_scope = IfConditionalAnalyzer::analyze(
                $statements_analyzer,
                $stmt->cond,
                $context,
                $codebase,
                $if_scope,
                $context->branch_point ?: (int) $stmt->getAttribute('startFilePos')
            );

            $if_context = $if_conditional_scope->if_context;

            $cond_referenced_var_ids = $if_conditional_scope->cond_referenced_var_ids;
            $assigned_in_conditional_var_ids = $if_conditional_scope->assigned_in_conditional_var_ids;
        } catch (ScopeAnalysisException $e) {
            return false;
        }

        $codebase = $statements_analyzer->getCodebase();

        $cond_id = spl_object_id($stmt->cond);

        $if_clauses = FormulaGenerator::getFormula(
            $cond_id,
            $cond_id,
            $stmt->cond,
            $context->self,
            $statements_analyzer,
            $codebase
        );

        $mixed_var_ids = [];

        foreach ($context->vars_in_scope as $var_id => $type) {
            if ($type->hasMixed()) {
                $mixed_var_ids[] = $var_id;
            }
        }

        foreach ($context->vars_possibly_in_scope as $var_id => $_) {
            if (!isset($context->vars_in_scope[$var_id])) {
                $mixed_var_ids[] = $var_id;
            }
        }

        $if_clauses = array_map(
            /**
             * @return Clause
             */
            function (Clause $c) use ($mixed_var_ids, $cond_id): Clause {
                $keys = array_keys($c->possibilities);

                $mixed_var_ids = array_diff($mixed_var_ids, $keys);

                foreach ($keys as $key) {
                    foreach ($mixed_var_ids as $mixed_var_id) {
                        if (preg_match('/^' . preg_quote($mixed_var_id, '/') . '(\[|-)/', $key)) {
                            return new Clause([], $cond_id, $cond_id, true);
                        }
                    }
                }

                return $c;
            },
            $if_clauses
        );

        // this will see whether any of the clauses in set A conflict with the clauses in set B
        AlgebraAnalyzer::checkForParadox(
            $context->clauses,
            $if_clauses,
            $statements_analyzer,
            $stmt->cond,
            $assigned_in_conditional_var_ids
        );

        $ternary_clauses = array_merge($context->clauses, $if_clauses);

        if ($if_context->reconciled_expression_clauses) {
            $reconciled_expression_clauses = $if_context->reconciled_expression_clauses;

            $ternary_clauses = array_values(
                array_filter(
                    $ternary_clauses,
                    function ($c) use ($reconciled_expression_clauses): bool {
                        return !in_array($c->hash, $reconciled_expression_clauses);
                    }
                )
            );
        }

        $ternary_clauses = Algebra::simplifyCNF($ternary_clauses);

        $negated_clauses = Algebra::negateFormula($if_clauses);

        $negated_if_types = Algebra::getTruthsFromFormula(
            Algebra::simplifyCNF(
                array_merge($context->clauses, $negated_clauses)
            )
        );

        $active_if_types = [];

        $reconcilable_if_types = Algebra::getTruthsFromFormula(
            $ternary_clauses,
            $cond_id,
            $cond_referenced_var_ids,
            $active_if_types
        );

        $changed_var_ids = [];

        if ($reconcilable_if_types) {
            $if_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                $reconcilable_if_types,
                $active_if_types,
                $if_context->vars_in_scope,
                $changed_var_ids,
                $cond_referenced_var_ids,
                $statements_analyzer,
                $statements_analyzer->getTemplateTypeMap() ?: [],
                $if_context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->cond)
            );

            $if_context->vars_in_scope = $if_vars_in_scope_reconciled;
        }

        $t_else_context = clone $context;

        if ($stmt->if) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->if, $if_context) === false) {
                return false;
            }

            $context->referenced_var_ids = array_merge(
                $context->referenced_var_ids,
                $if_context->referenced_var_ids
            );
        }

        $t_else_context->clauses = Algebra::simplifyCNF(
            array_merge(
                $t_else_context->clauses,
                $negated_clauses
            )
        );

        if ($negated_if_types) {
            $changed_var_ids = [];

            $t_else_vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                $negated_if_types,
                $negated_if_types,
                $t_else_context->vars_in_scope,
                $changed_var_ids,
                $cond_referenced_var_ids,
                $statements_analyzer,
                $statements_analyzer->getTemplateTypeMap() ?: [],
                $t_else_context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $stmt->else)
            );

            $t_else_context->vars_in_scope = $t_else_vars_in_scope_reconciled;

            $t_else_context->clauses = Context::removeReconciledClauses($t_else_context->clauses, $changed_var_ids)[0];
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->else, $t_else_context) === false) {
            return false;
        }

        $assign_var_ifs = $if_context->assigned_var_ids;
        $assign_var_else = $t_else_context->assigned_var_ids;
        $assign_all = array_intersect_key($assign_var_ifs, $assign_var_else);

        //if the same var was assigned in both branches
        foreach ($assign_all as $var_id => $_) {
            if (isset($if_context->vars_in_scope[$var_id]) && isset($t_else_context->vars_in_scope[$var_id])) {
                $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                    $if_context->vars_in_scope[$var_id],
                    $t_else_context->vars_in_scope[$var_id]
                );
            }
        }

        $redef_var_ifs = array_keys($if_context->getRedefinedVars($context->vars_in_scope));
        $redef_var_else = array_keys($t_else_context->getRedefinedVars($context->vars_in_scope));
        $redef_all = array_intersect($redef_var_ifs, $redef_var_else);

        //these vars were changed in both branches
        foreach ($redef_all as $redef_var_id) {
            $context->vars_in_scope[$redef_var_id] = Type::combineUnionTypes(
                $if_context->vars_in_scope[$redef_var_id],
                $t_else_context->vars_in_scope[$redef_var_id]
            );
        }

        //these vars were changed in the if and existed before
        foreach ($redef_var_ifs as $redef_var_ifs_id) {
            if (isset($context->vars_in_scope[$redef_var_ifs_id])) {
                $context->vars_in_scope[$redef_var_ifs_id] = Type::combineUnionTypes(
                    $context->vars_in_scope[$redef_var_ifs_id],
                    $if_context->vars_in_scope[$redef_var_ifs_id]
                );
            }
        }

        //these vars were changed in the else and existed before
        foreach ($redef_var_else as $redef_var_else_id) {
            if (isset($context->vars_in_scope[$redef_var_else_id])) {
                $context->vars_in_scope[$redef_var_else_id] = Type::combineUnionTypes(
                    $context->vars_in_scope[$redef_var_else_id],
                    $t_else_context->vars_in_scope[$redef_var_else_id]
                );
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $if_context->vars_possibly_in_scope,
            $t_else_context->vars_possibly_in_scope
        );

        $context->referenced_var_ids = array_merge(
            $context->referenced_var_ids,
            $t_else_context->referenced_var_ids
        );

        $lhs_type = null;

        if ($stmt->if) {
            if ($stmt_if_type = $statements_analyzer->node_data->getType($stmt->if)) {
                $lhs_type = $stmt_if_type;
            }
        } elseif ($stmt_cond_type = $statements_analyzer->node_data->getType($stmt->cond)) {
            $if_return_type_reconciled = AssertionReconciler::reconcile(
                '!falsy',
                clone $stmt_cond_type,
                '',
                $statements_analyzer,
                $context->inside_loop,
                [],
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $statements_analyzer->getSuppressedIssues()
            );

            $lhs_type = $if_return_type_reconciled;
        }

        if ($lhs_type && ($stmt_else_type = $statements_analyzer->node_data->getType($stmt->else))) {
            $statements_analyzer->node_data->setType($stmt, Type::combineUnionTypes($lhs_type, $stmt_else_type));
        } else {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        }

        return true;
    }
}
