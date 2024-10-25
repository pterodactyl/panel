<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TKeyedArray;

use function strtolower;

class YieldFromAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\YieldFrom $stmt,
        Context $context
    ): bool {
        $was_inside_call = $context->inside_call;

        $context->inside_call = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            $context->inside_call = $was_inside_call;

            return false;
        }

        if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
            $key_type = null;
            $value_type = null;
            $always_non_empty_array = true;
            if (ForeachAnalyzer::checkIteratorType(
                $statements_analyzer,
                $stmt,
                $stmt->expr,
                $stmt_expr_type,
                $statements_analyzer->getCodebase(),
                $context,
                $key_type,
                $value_type,
                $always_non_empty_array
            ) === false
            ) {
                $context->inside_call = $was_inside_call;

                return false;
            }

            $yield_from_type = null;

            foreach ($stmt_expr_type->getAtomicTypes() as $atomic_type) {
                if ($yield_from_type === null) {
                    if ($atomic_type instanceof TGenericObject
                        && strtolower($atomic_type->value) === 'generator'
                        && isset($atomic_type->type_params[3])
                    ) {
                        $yield_from_type = clone $atomic_type->type_params[3];
                    } elseif ($atomic_type instanceof TArray) {
                        $yield_from_type = clone $atomic_type->type_params[1];
                    } elseif ($atomic_type instanceof TKeyedArray) {
                        $yield_from_type = $atomic_type->getGenericValueType();
                    }
                } else {
                    $yield_from_type = Type::getMixed();
                }
            }

            // this should be whatever the generator above returns, but *not* the return type
            $statements_analyzer->node_data->setType($stmt, $yield_from_type ?: Type::getMixed());
        }

        $context->inside_call = $was_inside_call;

        return true;
    }
}
