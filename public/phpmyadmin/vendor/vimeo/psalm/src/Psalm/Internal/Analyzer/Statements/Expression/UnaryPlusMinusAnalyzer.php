<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use PhpParser\Node\Expr\UnaryMinus;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Type;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

class UnaryPlusMinusAnalyzer
{
    /**
     * @param PhpParser\Node\Expr\UnaryMinus|PhpParser\Node\Expr\UnaryPlus $stmt
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context
    ): bool {
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        if (!($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr))) {
            $statements_analyzer->node_data->setType($stmt, new Union([new TInt, new TFloat]));
        } elseif ($stmt_expr_type->isMixed()) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        } else {
            $acceptable_types = [];

            foreach ($stmt_expr_type->getAtomicTypes() as $type_part) {
                if ($type_part instanceof TInt || $type_part instanceof TFloat) {
                    if ($type_part instanceof TLiteralInt
                        && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                    ) {
                        $type_part->value = -$type_part->value;
                    } elseif ($type_part instanceof TLiteralFloat
                        && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                    ) {
                        $type_part->value = -$type_part->value;
                    }

                    if ($type_part instanceof TIntRange
                        && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                    ) {
                        //we'll have to inverse min and max bound and negate any literal
                        $old_min_bound = $type_part->min_bound;
                        $old_max_bound = $type_part->max_bound;
                        if ($old_min_bound === null) {
                            //min bound is null, max bound will be null
                            $type_part->max_bound = null;
                        } elseif ($old_min_bound === 0) {
                            $type_part->max_bound = 0;
                        } else {
                            $type_part->max_bound = -$old_min_bound;
                        }

                        if ($old_max_bound === null) {
                            //max bound is null, min bound will be null
                            $type_part->min_bound = null;
                        } elseif ($old_max_bound === 0) {
                            $type_part->min_bound = 0;
                        } else {
                            $type_part->min_bound = -$old_max_bound;
                        }
                    }

                    if ($type_part instanceof TPositiveInt
                        && $stmt instanceof PhpParser\Node\Expr\UnaryMinus
                    ) {
                        $type_part = new TIntRange(null, -1);
                    }

                    $acceptable_types[] = $type_part;
                } elseif ($type_part instanceof TString) {
                    $acceptable_types[] = new TInt;
                    $acceptable_types[] = new TFloat;
                } else {
                    $acceptable_types[] = new TInt;
                }
            }

            $statements_analyzer->node_data->setType($stmt, new Union($acceptable_types));
        }

        self::addDataFlow(
            $statements_analyzer,
            $stmt,
            $stmt->expr,
            $stmt instanceof UnaryMinus ? 'unary-minus' : 'unary-plus'
        );

        return true;
    }

    private static function addDataFlow(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        PhpParser\Node\Expr $value,
        string $type
    ): void {
        $result_type = $statements_analyzer->node_data->getType($stmt);
        if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph && $result_type) {
            $var_location = new CodeLocation($statements_analyzer, $stmt);

            $stmt_value_type = $statements_analyzer->node_data->getType($value);

            $new_parent_node = DataFlowNode::getForAssignment($type, $var_location);
            $statements_analyzer->data_flow_graph->addNode($new_parent_node);
            $result_type->parent_nodes = [
                $new_parent_node->id => $new_parent_node,
            ];

            if ($stmt_value_type && $stmt_value_type->parent_nodes) {
                foreach ($stmt_value_type->parent_nodes as $parent_node) {
                    $statements_analyzer->data_flow_graph->addPath($parent_node, $new_parent_node, $type);
                }
            }
        }
    }
}
