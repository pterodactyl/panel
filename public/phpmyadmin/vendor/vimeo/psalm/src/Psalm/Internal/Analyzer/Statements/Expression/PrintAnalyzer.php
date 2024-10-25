<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\ImpureFunctionCall;
use Psalm\IssueBuffer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\TaintKind;

class PrintAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Print_ $stmt,
        Context $context
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context) === false) {
            return false;
        }

        if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph) {
            $call_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

            $print_param_sink = TaintSink::getForMethodArgument(
                'print',
                'print',
                0,
                null,
                $call_location
            );

            $print_param_sink->taints = [
                TaintKind::INPUT_HTML,
                TaintKind::INPUT_HAS_QUOTES,
                TaintKind::USER_SECRET,
                TaintKind::SYSTEM_SECRET
            ];

            $statements_analyzer->data_flow_graph->addSink($print_param_sink);
        }

        if ($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr)) {
            if (ArgumentAnalyzer::verifyType(
                $statements_analyzer,
                $stmt_expr_type,
                Type::getString(),
                null,
                'print',
                null,
                0,
                new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                $stmt->expr,
                $context,
                new FunctionLikeParameter('var', false),
                false,
                null,
                true,
                true,
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            ) === false) {
                return false;
            }
        }

        if (isset($codebase->config->forbidden_functions['print'])) {
            IssueBuffer::maybeAdd(
                new ForbiddenCode(
                    'You have forbidden the use of print',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        }

        if (!$context->collect_initializations && !$context->collect_mutations) {
            if ($context->mutation_free || $context->external_mutation_free) {
                IssueBuffer::maybeAdd(
                    new ImpureFunctionCall(
                        'Cannot call print from a mutation-free context',
                        new CodeLocation($statements_analyzer, $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } elseif ($statements_analyzer->getSource() instanceof FunctionLikeAnalyzer
                && $statements_analyzer->getSource()->track_mutations
            ) {
                $statements_analyzer->getSource()->inferred_has_mutation = true;
                $statements_analyzer->getSource()->inferred_impure = true;
            }
        }

        $statements_analyzer->node_data->setType($stmt, Type::getInt(false, 1));

        return true;
    }
}
