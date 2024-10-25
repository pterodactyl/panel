<?php

namespace Psalm\Internal\Analyzer\Statements\Block;

use PhpParser;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Algebra;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Scope\SwitchScope;
use Psalm\Type;
use Psalm\Type\Reconciler;
use SplFixedArray;

use function array_merge;
use function count;
use function in_array;

/**
 * @internal
 */
class SwitchAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Switch_ $stmt,
        Context $context
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $was_inside_conditional = $context->inside_conditional;

        $context->inside_conditional = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->cond, $context) === false) {
            $context->inside_conditional = $was_inside_conditional;

            return;
        }

        $context->inside_conditional = $was_inside_conditional;

        $switch_var_id = ExpressionIdentifier::getArrayVarId(
            $stmt->cond,
            null,
            $statements_analyzer
        );

        if (!$switch_var_id
            && ($stmt->cond instanceof PhpParser\Node\Expr\FuncCall
                || $stmt->cond instanceof PhpParser\Node\Expr\MethodCall
                || $stmt->cond instanceof PhpParser\Node\Expr\StaticCall
            )
        ) {
            $switch_var_id = '$__tmp_switch__' . (int) $stmt->cond->getAttribute('startFilePos');

            $condition_type = $statements_analyzer->node_data->getType($stmt->cond) ?? Type::getMixed();

            $context->vars_in_scope[$switch_var_id] = $condition_type;
        }

        $original_context = clone $context;

        // the last statement always breaks, by default
        $last_case_exit_type = 'break';

        $case_exit_types = new SplFixedArray(count($stmt->cases));

        $has_default = false;

        $case_action_map = [];

        $config = Config::getInstance();

        // create a map of case statement -> ultimate exit type
        for ($i = count($stmt->cases) - 1; $i >= 0; --$i) {
            $case = $stmt->cases[$i];

            $case_actions = $case_action_map[$i] = ScopeAnalyzer::getControlActions(
                $case->stmts,
                $statements_analyzer->node_data,
                $config->exit_functions,
                ['switch']
            );

            if (!in_array(ScopeAnalyzer::ACTION_NONE, $case_actions, true)) {
                if ($case_actions === [ScopeAnalyzer::ACTION_END]) {
                    $last_case_exit_type = 'return_throw';
                } elseif ($case_actions === [ScopeAnalyzer::ACTION_CONTINUE]) {
                    $last_case_exit_type = 'continue';
                } elseif (in_array(ScopeAnalyzer::ACTION_LEAVE_SWITCH, $case_actions, true)) {
                    $last_case_exit_type = 'break';
                }
            } elseif (count($case_actions) !== 1) {
                $last_case_exit_type = 'hybrid';
            }

            $case_exit_types[$i] = $last_case_exit_type;
        }

        $switch_scope = new SwitchScope();

        $was_caching_assertions = $statements_analyzer->node_data->cache_assertions;

        $statements_analyzer->node_data->cache_assertions = false;

        $all_options_returned = true;

        for ($i = 0, $l = count($stmt->cases); $i < $l; $i++) {
            $case = $stmt->cases[$i];

            /** @var string */
            $case_exit_type = $case_exit_types[$i];
            if ($case_exit_type !== 'return_throw') {
                $all_options_returned = false;
            }

            $case_actions = $case_action_map[$i];

            if (!$case->cond) {
                $has_default = true;
            }

            if (SwitchCaseAnalyzer::analyze(
                $statements_analyzer,
                $codebase,
                $stmt,
                $switch_var_id,
                $case,
                $context,
                $original_context,
                $case_exit_type,
                $case_actions,
                $i === $l - 1,
                $switch_scope
            ) === false
            ) {
                return;
            }
        }

        $all_options_matched = $has_default;

        if (!$has_default && $switch_scope->negated_clauses && $switch_var_id) {
            $entry_clauses = Algebra::simplifyCNF(
                array_merge(
                    $original_context->clauses,
                    $switch_scope->negated_clauses
                )
            );

            $reconcilable_if_types = Algebra::getTruthsFromFormula($entry_clauses);

            // if the if has an || in the conditional, we cannot easily reason about it
            if ($reconcilable_if_types && isset($reconcilable_if_types[$switch_var_id])) {
                $changed_var_ids = [];

                $case_vars_in_scope_reconciled =
                    Reconciler::reconcileKeyedTypes(
                        $reconcilable_if_types,
                        [],
                        $original_context->vars_in_scope,
                        $changed_var_ids,
                        [],
                        $statements_analyzer,
                        [],
                        $original_context->inside_loop
                    );

                if (isset($case_vars_in_scope_reconciled[$switch_var_id])
                    && $case_vars_in_scope_reconciled[$switch_var_id]->isEmpty()
                ) {
                    $all_options_matched = true;
                }
            }
        }

        if ($was_caching_assertions) {
            $statements_analyzer->node_data->cache_assertions = true;
        }

        // only update vars if there is a default or all possible cases accounted for
        // if the default has a throw/return/continue, that should be handled above
        if ($all_options_matched) {
            if ($switch_scope->new_vars_in_scope) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $switch_scope->new_vars_in_scope);
            }

            if ($switch_scope->redefined_vars) {
                $context->vars_in_scope = array_merge($context->vars_in_scope, $switch_scope->redefined_vars);
            }

            if ($switch_scope->possibly_redefined_vars) {
                foreach ($switch_scope->possibly_redefined_vars as $var_id => $type) {
                    if (!isset($switch_scope->redefined_vars[$var_id])
                        && !isset($switch_scope->new_vars_in_scope[$var_id])
                        && isset($context->vars_in_scope[$var_id])
                    ) {
                        $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $type,
                            $context->vars_in_scope[$var_id]
                        );
                    }
                }
            }

            $stmt->setAttribute('allMatched', true);
        } elseif ($switch_scope->possibly_redefined_vars) {
            foreach ($switch_scope->possibly_redefined_vars as $var_id => $type) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->vars_in_scope[$var_id] = Type::combineUnionTypes(
                        $type,
                        $context->vars_in_scope[$var_id]
                    );
                }
            }
        }

        if ($switch_scope->new_assigned_var_ids) {
            $context->assigned_var_ids += $switch_scope->new_assigned_var_ids;
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $switch_scope->new_vars_possibly_in_scope
        );

        //a switch can't return in all options without a default
        $context->has_returned = $all_options_returned && $has_default;
    }
}
