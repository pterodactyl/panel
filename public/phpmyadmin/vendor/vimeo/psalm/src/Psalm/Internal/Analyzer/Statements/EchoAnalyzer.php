<?php

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CastAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\ForbiddenEcho;
use Psalm\Issue\ImpureFunctionCall;
use Psalm\IssueBuffer;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\TaintKind;

class EchoAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Echo_ $stmt,
        Context $context
    ): bool {
        $echo_param = new FunctionLikeParameter(
            'var',
            false
        );

        $codebase = $statements_analyzer->getCodebase();

        foreach ($stmt->exprs as $i => $expr) {
            $context->inside_call = true;
            ExpressionAnalyzer::analyze($statements_analyzer, $expr, $context);
            $context->inside_call = false;

            $expr_type = $statements_analyzer->node_data->getType($expr);

            if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph) {
                if ($expr_type && $expr_type->hasObjectType()) {
                    $expr_type = CastAnalyzer::castStringAttempt(
                        $statements_analyzer,
                        $context,
                        $expr_type,
                        $expr,
                        false
                    );
                }

                $call_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

                $echo_param_sink = TaintSink::getForMethodArgument(
                    'echo',
                    'echo',
                    (int) $i,
                    null,
                    $call_location
                );

                $echo_param_sink->taints = [
                    TaintKind::INPUT_HTML,
                    TaintKind::INPUT_HAS_QUOTES,
                    TaintKind::USER_SECRET,
                    TaintKind::SYSTEM_SECRET
                ];

                $statements_analyzer->data_flow_graph->addSink($echo_param_sink);
            }

            if (ArgumentAnalyzer::verifyType(
                $statements_analyzer,
                $expr_type ?? Type::getMixed(),
                Type::getString(),
                null,
                'echo',
                null,
                (int)$i,
                new CodeLocation($statements_analyzer->getSource(), $expr),
                $expr,
                $context,
                $echo_param,
                false,
                null,
                true,
                true,
                new CodeLocation($statements_analyzer, $stmt)
            ) === false) {
                return false;
            }
        }

        if ($codebase->config->forbid_echo) {
            if (IssueBuffer::accepts(
                new ForbiddenEcho(
                    'Use of echo',
                    new CodeLocation($statements_analyzer, $stmt)
                ),
                $statements_analyzer->getSource()->getSuppressedIssues()
            )) {
                return false;
            }
        } elseif (isset($codebase->config->forbidden_functions['echo'])) {
            IssueBuffer::maybeAdd(
                new ForbiddenCode(
                    'Use of echo',
                    new CodeLocation($statements_analyzer, $stmt)
                ),
                $statements_analyzer->getSource()->getSuppressedIssues()
            );
        }

        if (!$context->collect_initializations && !$context->collect_mutations) {
            if ($context->mutation_free || $context->external_mutation_free) {
                IssueBuffer::maybeAdd(
                    new ImpureFunctionCall(
                        'Cannot call echo from a mutation-free context',
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

        return true;
    }
}
