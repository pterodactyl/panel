<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Node\Expr\BinaryOp\VirtualIdentical;
use Psalm\Node\Expr\VirtualConstFetch;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualPropertyFetch;
use Psalm\Node\Expr\VirtualTernary;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\VirtualName;
use Psalm\Type;

/**
 * @internal
 */
class NullsafeAnalyzer
{
    /**
     * @param PhpParser\Node\Expr\NullsafePropertyFetch|PhpParser\Node\Expr\NullsafeMethodCall $stmt
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context
    ): bool {
        if (!$stmt->var instanceof PhpParser\Node\Expr\Variable) {
            $was_inside_general_use = $context->inside_general_use;

            $context->inside_general_use = true;
            ExpressionAnalyzer::analyze($statements_analyzer, $stmt->var, $context);
            $context->inside_general_use = $was_inside_general_use;

            $tmp_name = '__tmp_nullsafe__' . (int) $stmt->var->getAttribute('startFilePos');

            $condition_type = $statements_analyzer->node_data->getType($stmt->var);

            if ($condition_type) {
                $context->vars_in_scope['$' . $tmp_name] = $condition_type;

                $tmp_var = new VirtualVariable($tmp_name, $stmt->var->getAttributes());
            } else {
                $tmp_var = $stmt->var;
            }
        } else {
            $tmp_var = $stmt->var;
        }

        $old_node_data = $statements_analyzer->node_data;
        $statements_analyzer->node_data = clone $statements_analyzer->node_data;

        $null_value1 = new VirtualConstFetch(
            new VirtualName('null'),
            $stmt->var->getAttributes()
        );

        $null_comparison = new VirtualIdentical(
            $tmp_var,
            $null_value1,
            $stmt->var->getAttributes()
        );

        $null_value2 = new VirtualConstFetch(
            new VirtualName('null'),
            $stmt->var->getAttributes()
        );

        if ($stmt instanceof PhpParser\Node\Expr\NullsafePropertyFetch) {
            $ternary = new VirtualTernary(
                $null_comparison,
                $null_value2,
                new VirtualPropertyFetch($tmp_var, $stmt->name, $stmt->getAttributes()),
                $stmt->getAttributes()
            );
        } else {
            $ternary = new VirtualTernary(
                $null_comparison,
                $null_value2,
                new VirtualMethodCall($tmp_var, $stmt->name, $stmt->args, $stmt->getAttributes()),
                $stmt->getAttributes()
            );
        }

        ExpressionAnalyzer::analyze($statements_analyzer, $ternary, $context);

        $ternary_type = $statements_analyzer->node_data->getType($ternary);

        $statements_analyzer->node_data = $old_node_data;

        $statements_analyzer->node_data->setType($stmt, $ternary_type ?? Type::getMixed());

        return true;
    }
}
