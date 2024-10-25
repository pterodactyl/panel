<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;

class BitwiseNotAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\BitwiseNot $stmt,
        Context $context
    ): bool {
        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        if (!($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr))) {
            $statements_analyzer->node_data->setType($stmt, new Union([new TInt(), new TString()]));
        } elseif ($stmt_expr_type->isMixed()) {
            $statements_analyzer->node_data->setType($stmt, Type::getMixed());
        } else {
            $acceptable_types = [];
            $unacceptable_type = null;
            $has_valid_operand = false;

            foreach ($stmt_expr_type->getAtomicTypes() as $type_string => $type_part) {
                if ($type_part instanceof TInt || $type_part instanceof TString) {
                    if ($type_part instanceof TLiteralInt) {
                        $type_part->value = ~$type_part->value;
                    } elseif ($type_part instanceof TLiteralString) {
                        $type_part->value = ~$type_part->value;
                    }

                    $acceptable_types[] = $type_part;
                    $has_valid_operand = true;
                } elseif ($type_part instanceof TFloat) {
                    $type_part = ($type_part instanceof TLiteralFloat) ?
                        new TLiteralInt(~$type_part->value) :
                        new TInt;

                    $stmt_expr_type->removeType($type_string);
                    $stmt_expr_type->addType($type_part);

                    $acceptable_types[] = $type_part;
                    $has_valid_operand = true;
                } elseif (!$unacceptable_type) {
                    $unacceptable_type = $type_part;
                }
            }

            if ($unacceptable_type || !$acceptable_types) {
                $message = 'Cannot negate a non-numeric non-string type ' . $unacceptable_type;
                if ($has_valid_operand) {
                    IssueBuffer::maybeAdd(
                        new PossiblyInvalidOperand(
                            $message,
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new InvalidOperand(
                            $message,
                            new CodeLocation($statements_analyzer, $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                }

                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            } else {
                $statements_analyzer->node_data->setType($stmt, new Union($acceptable_types));
            }
        }

        self::addDataFlow($statements_analyzer, $stmt, $stmt->expr);

        return true;
    }

    private static function addDataFlow(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        PhpParser\Node\Expr $value
    ): void {
        $result_type = $statements_analyzer->node_data->getType($stmt);
        if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph && $result_type) {
            $var_location = new CodeLocation($statements_analyzer, $stmt);

            $stmt_value_type = $statements_analyzer->node_data->getType($value);

            $new_parent_node = DataFlowNode::getForAssignment('bitwisenot', $var_location);
            $statements_analyzer->data_flow_graph->addNode($new_parent_node);
            $result_type->parent_nodes = [
                $new_parent_node->id => $new_parent_node,
            ];

            if ($stmt_value_type && $stmt_value_type->parent_nodes) {
                foreach ($stmt_value_type->parent_nodes as $parent_node) {
                    $statements_analyzer->data_flow_graph->addPath($parent_node, $new_parent_node, 'bitwisenot');
                }
            }
        }
    }
}
