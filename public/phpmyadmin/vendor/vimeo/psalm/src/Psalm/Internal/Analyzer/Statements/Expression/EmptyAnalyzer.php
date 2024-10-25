<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Type;

class EmptyAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Empty_ $stmt,
        Context $context
    ): void {
        IssetAnalyzer::analyzeIssetVar($statements_analyzer, $stmt->expr, $context);

        $codebase = $statements_analyzer->getCodebase();

        if (isset($codebase->config->forbidden_functions['empty'])) {
            IssueBuffer::maybeAdd(
                new ForbiddenCode(
                    'You have forbidden the use of empty',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        }

        if (($stmt_expr_type = $statements_analyzer->node_data->getType($stmt->expr))
            && $stmt_expr_type->hasBool()
            && $stmt_expr_type->isSingle()
            && !$stmt_expr_type->from_docblock
        ) {
            IssueBuffer::maybeAdd(
                new InvalidArgument(
                    'Calling empty on a boolean value is almost certainly unintended',
                    new CodeLocation($statements_analyzer->getSource(), $stmt->expr),
                    'empty'
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        }

        $statements_analyzer->node_data->setType($stmt, Type::getBool());
    }
}
