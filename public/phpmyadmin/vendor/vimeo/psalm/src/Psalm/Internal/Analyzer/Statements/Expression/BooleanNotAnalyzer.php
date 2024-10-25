<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type;

class BooleanNotAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BooleanNot $stmt,
        Context $context
    ): bool {


        $inside_negation = $context->inside_negation;

        $context->inside_negation = !$inside_negation;

        $result = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context);

        $context->inside_negation = $inside_negation;

        $expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        $stmt_type = Type::getBool();
        if ($expr_type) {
            if ($expr_type->isAlwaysTruthy()) {
                $stmt_type = Type::getFalse();
            } elseif ($expr_type->isAlwaysFalsy()) {
                $stmt_type = Type::getTrue();
            }

            $stmt_type->from_docblock = $expr_type->from_docblock;
            $stmt_type->parent_nodes = $expr_type->parent_nodes;
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return $result;
    }
}
