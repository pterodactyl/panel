<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\UnhandledMatchCondition;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\BinaryOp\VirtualIdentical;
use Psalm\Node\Expr\VirtualArray;
use Psalm\Node\Expr\VirtualArrayItem;
use Psalm\Node\Expr\VirtualConstFetch;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualNew;
use Psalm\Node\Expr\VirtualTernary;
use Psalm\Node\Expr\VirtualThrow;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\Name\VirtualFullyQualified;
use Psalm\Node\VirtualArg;
use Psalm\Type;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Reconciler;
use UnexpectedValueException;

use function array_filter;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_shift;
use function count;
use function in_array;
use function spl_object_id;
use function substr;

class MatchAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Match_ $stmt,
        Context $context
    ): bool {
        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        $was_inside_conditional = $context->inside_conditional;

        $context->inside_conditional = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->cond, $context) === false) {
            $context->inside_conditional = $was_inside_conditional;

            return false;
        }

        $context->inside_conditional = $was_inside_conditional;

        $switch_var_id = ExpressionIdentifier::getArrayVarId(
            $stmt->cond,
            null,
            $statements_analyzer
        );

        $match_condition = $stmt->cond;

        if (!$switch_var_id) {
            if ($stmt->cond instanceof PhpParser\Node\Expr\FuncCall
                && $stmt->cond->name instanceof PhpParser\Node\Name
                && ($stmt->cond->name->parts === ['get_class']
                    || $stmt->cond->name->parts === ['gettype']
                    || $stmt->cond->name->parts === ['get_debug_type'])
                && $stmt->cond->getArgs()
            ) {
                $first_arg = $stmt->cond->getArgs()[0];

                if (!$first_arg->value instanceof PhpParser\Node\Expr\Variable) {
                    $switch_var_id = '$__tmp_switch__' . (int) $first_arg->value->getAttribute('startFilePos');

                    $condition_type = $statements_analyzer->node_data->getType($first_arg->value) ?? Type::getMixed();

                    $context->vars_in_scope[$switch_var_id] = $condition_type;

                    $match_condition = new VirtualFuncCall(
                        $stmt->cond->name,
                        [
                            new VirtualArg(
                                new VirtualVariable(
                                    substr($switch_var_id, 1),
                                    $first_arg->value->getAttributes()
                                ),
                                false,
                                false,
                                $first_arg->getAttributes()
                            )
                        ],
                        $stmt->cond->getAttributes()
                    );
                }
            } elseif ($stmt->cond instanceof PhpParser\Node\Expr\FuncCall
                || $stmt->cond instanceof PhpParser\Node\Expr\MethodCall
                || $stmt->cond instanceof PhpParser\Node\Expr\StaticCall
            ) {
                $switch_var_id = '$__tmp_switch__' . (int) $stmt->cond->getAttribute('startFilePos');

                $condition_type = $statements_analyzer->node_data->getType($stmt->cond) ?? Type::getMixed();

                $context->vars_in_scope[$switch_var_id] = $condition_type;

                $match_condition = new VirtualVariable(
                    substr($switch_var_id, 1),
                    $stmt->cond->getAttributes()
                );
            }
        }

        $arms = $stmt->arms;

        foreach ($arms as $i => $arm) {
            // move default to the end
            if ($arm->conds === null) {
                unset($arms[$i]);
                $arms[] = $arm;
            }
        }

        $arms = array_reverse($arms);

        $last_arm = array_shift($arms);

        if (!$last_arm) {
            IssueBuffer::maybeAdd(
                new UnhandledMatchCondition(
                    'This match expression does not match anything',
                    new CodeLocation($statements_analyzer->getSource(), $match_condition)
                ),
                $statements_analyzer->getSuppressedIssues()
            );

            return false;
        }

        $old_node_data = $statements_analyzer->node_data;

        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        if (!$last_arm->conds) {
            $ternary = $last_arm->body;
        } else {
            $ternary = new VirtualTernary(
                self::convertCondsToConditional($last_arm->conds, $match_condition, $last_arm->getAttributes()),
                $last_arm->body,
                new VirtualThrow(
                    new VirtualNew(
                        new VirtualFullyQualified(
                            'UnhandledMatchError',
                            $stmt->getAttributes()
                        ),
                        [],
                        $stmt->getAttributes()
                    )
                ),
                $stmt->getAttributes()
            );
        }

        foreach ($arms as $arm) {
            if (!$arm->conds) {
                continue;
            }

            $ternary = new VirtualTernary(
                self::convertCondsToConditional($arm->conds, $match_condition, $arm->getAttributes()),
                $arm->body,
                $ternary,
                $arm->getAttributes()
            );
        }

        $suppressed_issues = $statements_analyzer->getSuppressedIssues();

        if (!in_array('RedundantCondition', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['RedundantCondition']);
        }

        if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
            $statements_analyzer->addSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $ternary, $context) === false) {
            return false;
        }

        if (!in_array('RedundantCondition', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['RedundantCondition']);
        }

        if (!in_array('RedundantConditionGivenDocblockType', $suppressed_issues, true)) {
            $statements_analyzer->removeSuppressedIssues(['RedundantConditionGivenDocblockType']);
        }

        if ($switch_var_id && $last_arm->conds) {
            $codebase = $statements_analyzer->getCodebase();

            $all_conds = $last_arm->conds;

            foreach ($arms as $arm) {
                if (!$arm->conds) {
                    throw new UnexpectedValueException('bad');
                }

                $all_conds = array_merge($arm->conds, $all_conds);
            }

            $all_match_condition = self::convertCondsToConditional(
                $all_conds,
                $match_condition,
                $match_condition->getAttributes()
            );

            ExpressionAnalyzer::analyze($statements_analyzer, $all_match_condition, $context);

            $clauses = FormulaGenerator::getFormula(
                spl_object_id($all_match_condition),
                spl_object_id($all_match_condition),
                $all_match_condition,
                $context->self,
                $statements_analyzer,
                $codebase,
                false,
                false
            );

            $reconcilable_types = Algebra::getTruthsFromFormula(
                Algebra::negateFormula($clauses)
            );

            // if the if has an || in the conditional, we cannot easily reason about it
            if ($reconcilable_types) {
                $changed_var_ids = [];

                $vars_in_scope_reconciled = Reconciler::reconcileKeyedTypes(
                    $reconcilable_types,
                    [],
                    $context->vars_in_scope,
                    $changed_var_ids,
                    [],
                    $statements_analyzer,
                    [],
                    $context->inside_loop,
                    null
                );

                if (isset($vars_in_scope_reconciled[$switch_var_id])) {
                    $array_literal_types = array_filter(
                        $vars_in_scope_reconciled[$switch_var_id]->getAtomicTypes(),
                        function ($type) {
                            return $type instanceof TLiteralInt
                                || $type instanceof TLiteralString
                                || $type instanceof TLiteralFloat
                                || $type instanceof TEnumCase;
                        }
                    );

                    if ($array_literal_types) {
                        IssueBuffer::maybeAdd(
                            new UnhandledMatchCondition(
                                'This match expression is not exhaustive - consider values '
                                    . $vars_in_scope_reconciled[$switch_var_id]->getId(),
                                new CodeLocation($statements_analyzer->getSource(), $match_condition)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        );
                    }
                }
            }
        }

        $stmt_expr_type = $statements_analyzer->node_data->getType($ternary);

        $old_node_data->setType($stmt, $stmt_expr_type ?? Type::getMixed());

        $statements_analyzer->node_data = $old_node_data;

        $context->inside_call = $was_inside_call;

        return true;
    }

    /**
     * @param non-empty-list<PhpParser\Node\Expr> $conds
     */
    private static function convertCondsToConditional(
        array $conds,
        PhpParser\Node\Expr $match_condition,
        array $attributes
    ): PhpParser\Node\Expr {
        if (count($conds) === 1) {
            return new VirtualIdentical(
                $match_condition,
                $conds[0],
                $attributes
            );
        }

        $array_items = array_map(
            function ($cond): PhpParser\Node\Expr\ArrayItem {
                return new VirtualArrayItem($cond, null, false, $cond->getAttributes());
            },
            $conds
        );

        return new VirtualFuncCall(
            new VirtualFullyQualified(['in_array']),
            [
                new VirtualArg(
                    $match_condition,
                    false,
                    false,
                    $attributes
                ),
                new VirtualArg(
                    new VirtualArray(
                        $array_items,
                        $attributes
                    ),
                    false,
                    false,
                    $attributes
                ),
                new VirtualArg(
                    new VirtualConstFetch(
                        new VirtualFullyQualified(['true']),
                        $attributes
                    ),
                    false,
                    false,
                    $attributes
                ),
            ],
            $attributes
        );
    }
}
