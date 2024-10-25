<?php

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ArrayAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\AssertionFinder;
use Psalm\Internal\Analyzer\Statements\Expression\AssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BinaryOpAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BitwiseNotAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\BooleanNotAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\FunctionCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\NewAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\StaticCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CastAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CloneAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\EmptyAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\EncapsulatedStringAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\EvalAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExitAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ClassConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\InstancePropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\StaticPropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\IncDecExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\IncludeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\InstanceofAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\IssetAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\MagicConstAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\MatchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\NullsafeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\PrintAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\TernaryAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\UnaryPlusMinusAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\YieldAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\YieldFromAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\DataFlow\TaintSink;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\UnrecognizedExpression;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\TaintKind;

use function get_class;
use function in_array;
use function strtolower;

/**
 * @internal
 */
class ExpressionAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context,
        bool $array_assignment = false,
        ?Context $global_context = null,
        bool $from_stmt = false
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        if (self::handleExpression(
            $statements_analyzer,
            $stmt,
            $context,
            $array_assignment,
            $global_context,
            $from_stmt
        ) === false
        ) {
            return false;
        }

        if (!$context->inside_conditional
            && ($stmt instanceof PhpParser\Node\Expr\BinaryOp
                || $stmt instanceof PhpParser\Node\Expr\Instanceof_
                || $stmt instanceof PhpParser\Node\Expr\Assign
                || $stmt instanceof PhpParser\Node\Expr\BooleanNot
                || $stmt instanceof PhpParser\Node\Expr\Empty_
                || $stmt instanceof PhpParser\Node\Expr\Isset_
                || $stmt instanceof PhpParser\Node\Expr\FuncCall)
        ) {
            $assertions = $statements_analyzer->node_data->getAssertions($stmt);

            if ($assertions === null) {
                $negate = $context->inside_negation;

                while ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
                    $stmt = $stmt->expr;
                    $negate = !$negate;
                }

                AssertionFinder::scrapeAssertions(
                    $stmt,
                    $context->self,
                    $statements_analyzer,
                    $codebase,
                    $negate,
                    true,
                    false
                );
            }
        }

        $event = new AfterExpressionAnalysisEvent(
            $stmt,
            $context,
            $statements_analyzer,
            $codebase,
            []
        );

        if ($codebase->config->eventDispatcher->dispatchAfterExpressionAnalysis($event) === false) {
            return false;
        }

        $file_manipulations = $event->getFileReplacements();

        if ($file_manipulations) {
            FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
        }

        return true;
    }

    private static function handleExpression(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $stmt,
        Context $context,
        bool $array_assignment,
        ?Context $global_context,
        bool $from_stmt
    ): bool {
        if ($stmt instanceof PhpParser\Node\Expr\Variable) {
            return VariableFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                false,
                null,
                $array_assignment
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Assign) {
            $assignment_type = AssignmentAnalyzer::analyze(
                $statements_analyzer,
                $stmt->var,
                $stmt->expr,
                null,
                $context,
                $stmt->getDocComment()
            );

            if ($assignment_type === false) {
                return false;
            }

            if (!$from_stmt) {
                $statements_analyzer->node_data->setType($stmt, $assignment_type);
            }

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\AssignOp) {
            return AssignmentAnalyzer::analyzeAssignmentOperation($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\MethodCall) {
            return MethodCallAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\StaticCall) {
            return StaticCallAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ConstFetch) {
            ConstFetchAnalyzer::analyze($statements_analyzer, $stmt, $context);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\String_) {
            $statements_analyzer->node_data->setType($stmt, Type::getString($stmt->value));

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\EncapsedStringPart) {
            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\MagicConst) {
            MagicConstAnalyzer::analyze($statements_analyzer, $stmt, $context);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\LNumber) {
            $statements_analyzer->node_data->setType($stmt, Type::getInt(false, $stmt->value));

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Scalar\DNumber) {
            $statements_analyzer->node_data->setType($stmt, Type::getFloat($stmt->value));

            return true;
        }


        if ($stmt instanceof PhpParser\Node\Expr\UnaryMinus || $stmt instanceof PhpParser\Node\Expr\UnaryPlus) {
            return UnaryPlusMinusAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Isset_) {
            IssetAnalyzer::analyze($statements_analyzer, $stmt, $context);
            $statements_analyzer->node_data->setType($stmt, Type::getBool());

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ClassConstFetch) {
            return ClassConstFetchAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\PropertyFetch) {
            return InstancePropertyFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                $array_assignment
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
            return StaticPropertyFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\BitwiseNot) {
            return BitwiseNotAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\BinaryOp) {
            return BinaryOpAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context,
                0,
                $from_stmt
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\PostInc
            || $stmt instanceof PhpParser\Node\Expr\PostDec
            || $stmt instanceof PhpParser\Node\Expr\PreInc
            || $stmt instanceof PhpParser\Node\Expr\PreDec
        ) {
            return IncDecExpressionAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\New_) {
            return NewAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Array_) {
            return ArrayAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Scalar\Encapsed) {
            return EncapsulatedStringAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\FuncCall) {
            return FunctionCallAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Ternary) {
            return TernaryAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\BooleanNot) {
            return BooleanNotAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Empty_) {
            EmptyAnalyzer::analyze($statements_analyzer, $stmt, $context);

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Closure
            || $stmt instanceof PhpParser\Node\Expr\ArrowFunction
        ) {
            return ClosureAnalyzer::analyzeExpression($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            return ArrayFetchAnalyzer::analyze(
                $statements_analyzer,
                $stmt,
                $context
            );
        }

        if ($stmt instanceof PhpParser\Node\Expr\Cast) {
            return CastAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Clone_) {
            return CloneAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Instanceof_) {
            return InstanceofAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Exit_) {
            return ExitAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Include_) {
            return IncludeAnalyzer::analyze($statements_analyzer, $stmt, $context, $global_context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Eval_) {
            EvalAnalyzer::analyze($statements_analyzer, $stmt, $context);
            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\AssignRef) {
            return AssignmentAnalyzer::analyzeAssignmentRef($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\ErrorSuppress) {
            $context->error_suppressing = true;
            if (self::analyze($statements_analyzer, $stmt->expr, $context) === false) {
                return false;
            }
            $context->error_suppressing = false;

            $expr_type = $statements_analyzer->node_data->getType($stmt->expr);

            if ($expr_type) {
                $statements_analyzer->node_data->setType($stmt, $expr_type);
            }

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\ShellExec) {
            if ($statements_analyzer->data_flow_graph) {
                $call_location = new CodeLocation($statements_analyzer->getSource(), $stmt);

                if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph) {
                    $sink = TaintSink::getForMethodArgument(
                        'shell_exec',
                        'shell_exec',
                        0,
                        null,
                        $call_location
                    );

                    $sink->taints = [TaintKind::INPUT_SHELL];

                    $statements_analyzer->data_flow_graph->addSink($sink);
                }

                foreach ($stmt->parts as $part) {
                    if ($part instanceof PhpParser\Node\Expr\Variable) {
                        if (self::analyze($statements_analyzer, $part, $context) === false) {
                            break;
                        }

                        $expr_type = $statements_analyzer->node_data->getType($part);
                        if ($expr_type === null) {
                            break;
                        }

                        $shell_exec_param = new FunctionLikeParameter(
                            'var',
                            false
                        );

                        if (ArgumentAnalyzer::verifyType(
                            $statements_analyzer,
                            $expr_type,
                            Type::getString(),
                            null,
                            'shell_exec',
                            null,
                            0,
                            $call_location,
                            $stmt,
                            $context,
                            $shell_exec_param,
                            false,
                            null,
                            true,
                            true,
                            new CodeLocation($statements_analyzer, $stmt)
                        ) === false) {
                            return false;
                        }

                        foreach ($expr_type->parent_nodes as $parent_node) {
                            $statements_analyzer->data_flow_graph->addPath(
                                $parent_node,
                                new DataFlowNode('variable-use', 'variable use', null),
                                'variable-use'
                            );
                        }
                    }
                }
            }

            IssueBuffer::maybeAdd(
                new ForbiddenCode(
                    'Use of shell_exec',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            );

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Print_) {
            $was_inside_call = $context->inside_call;
            $context->inside_call = true;
            if (PrintAnalyzer::analyze($statements_analyzer, $stmt, $context) === false) {
                $context->inside_call = $was_inside_call;

                return false;
            }
            $context->inside_call = $was_inside_call;

            return true;
        }

        if ($stmt instanceof PhpParser\Node\Expr\Yield_) {
            return YieldAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\YieldFrom) {
            return YieldFromAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        $php_major_version = $statements_analyzer->getCodebase()->php_major_version;
        $php_minor_version = $statements_analyzer->getCodebase()->php_minor_version;

        if ($stmt instanceof PhpParser\Node\Expr\Match_ && $php_major_version >= 8) {
            return MatchAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Throw_ && $php_major_version >= 8) {
            return ThrowAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if (($stmt instanceof PhpParser\Node\Expr\NullsafePropertyFetch
                || $stmt instanceof PhpParser\Node\Expr\NullsafeMethodCall)
            && $php_major_version >= 8
        ) {
            return NullsafeAnalyzer::analyze($statements_analyzer, $stmt, $context);
        }

        if ($stmt instanceof PhpParser\Node\Expr\Error) {
            // do nothing
            return true;
        }

        if (IssueBuffer::accepts(
            new UnrecognizedExpression(
                'Psalm does not understand ' . get_class($stmt) . ' for PHP ' .
                $php_major_version . ' ' . $php_minor_version,
                new CodeLocation($statements_analyzer->getSource(), $stmt)
            ),
            $statements_analyzer->getSuppressedIssues()
        )) {
           // fall through
        }

        return false;
    }

    public static function isMock(string $fq_class_name): bool
    {
        return in_array(strtolower($fq_class_name), Config::getInstance()->getMockClasses(), true);
    }
}
