<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use PhpParser\Node\Scalar\EncapsedStringPart;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Union;

use function assert;
use function in_array;

class EncapsulatedStringAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Scalar\Encapsed $stmt,
        Context $context
    ): bool {
        $stmt_type = Type::getString();

        $non_empty = false;

        $all_literals = true;

        $literal_string = "";

        foreach ($stmt->parts as $part) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $part, $context) === false) {
                return false;
            }

            $part_type = $statements_analyzer->node_data->getType($part);

            if ($part_type !== null) {
                $casted_part_type = CastAnalyzer::castStringAttempt(
                    $statements_analyzer,
                    $context,
                    $part_type,
                    $part
                );

                if (!$casted_part_type->allLiterals()) {
                    $all_literals = false;
                } elseif (!$non_empty) {
                    // Check if all literals are nonempty
                    $non_empty = true;
                    foreach ($casted_part_type->getAtomicTypes() as $atomic_literal) {
                        if (!$atomic_literal instanceof TLiteralInt
                            && !$atomic_literal instanceof TNonspecificLiteralInt
                            && !$atomic_literal instanceof TLiteralFloat
                            && !$atomic_literal instanceof TNonEmptyNonspecificLiteralString
                            && !($atomic_literal instanceof TLiteralString && $atomic_literal->value !== "")
                        ) {
                            $non_empty = false;
                            break;
                        }
                    }
                }

                if ($literal_string !== null) {
                    if ($casted_part_type->isSingleLiteral()) {
                        $literal_string .= $casted_part_type->getSingleLiteral()->value;
                    } else {
                        $literal_string = null;
                    }
                }

                if ($statements_analyzer->data_flow_graph
                    && !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
                ) {
                    $var_location = new CodeLocation($statements_analyzer, $part);

                    $new_parent_node = DataFlowNode::getForAssignment('concat', $var_location);
                    $statements_analyzer->data_flow_graph->addNode($new_parent_node);

                    $stmt_type->parent_nodes[$new_parent_node->id] = $new_parent_node;

                    $codebase = $statements_analyzer->getCodebase();
                    $event = new AddRemoveTaintsEvent($stmt, $context, $statements_analyzer, $codebase);

                    $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
                    $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

                    if ($casted_part_type->parent_nodes) {
                        foreach ($casted_part_type->parent_nodes as $parent_node) {
                            $statements_analyzer->data_flow_graph->addPath(
                                $parent_node,
                                $new_parent_node,
                                'concat',
                                $added_taints,
                                $removed_taints
                            );
                        }
                    }
                }
            } elseif ($part instanceof EncapsedStringPart) {
                if ($literal_string !== null) {
                    $literal_string .= $part->value;
                }
                $non_empty = $non_empty || $part->value !== "";
            } else {
                $all_literals = false;
                $literal_string = null;
            }
        }

        if ($non_empty) {
            if ($literal_string !== null) {
                $new_type = Type::getString($literal_string);
            } elseif ($all_literals) {
                $new_type = new Union([new TNonEmptyNonspecificLiteralString()]);
            } else {
                $new_type = new Union([new TNonEmptyString()]);
            }
        } elseif ($all_literals) {
            $new_type = new Union([new TNonspecificLiteralString()]);
        }
        if (isset($new_type)) {
            assert($new_type instanceof Union);
            $new_type->parent_nodes = $stmt_type->parent_nodes;
            $stmt_type = $new_type;
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return true;
    }
}
