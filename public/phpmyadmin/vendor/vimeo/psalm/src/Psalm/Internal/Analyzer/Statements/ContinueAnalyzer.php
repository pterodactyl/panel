<?php

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\ScopeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\ContinueOutsideLoop;
use Psalm\IssueBuffer;
use Psalm\Type;

use function end;

class ContinueAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Continue_ $stmt,
        Context $context
    ): void {
        $count = $stmt->num instanceof PhpParser\Node\Scalar\LNumber? $stmt->num->value : 1;

        $loop_scope = $context->loop_scope;

        if ($count === 2 && isset($loop_scope->loop_parent_context->loop_scope)) {
            $loop_scope = $loop_scope->loop_parent_context->loop_scope;
        }

        if ($count === 3 && isset($loop_scope->loop_parent_context->loop_scope)) {
            $loop_scope = $loop_scope->loop_parent_context->loop_scope;
        }

        if ($loop_scope === null) {
            if (!$context->break_types) {
                if (IssueBuffer::accepts(
                    new ContinueOutsideLoop(
                        'Continue call outside loop context',
                        new CodeLocation($statements_analyzer, $stmt)
                    ),
                    $statements_analyzer->getSource()->getSuppressedIssues()
                )) {
                    return;
                }
            }
        } else {
            if ($context->break_types
                && end($context->break_types) === 'switch'
                && $count < 2
            ) {
                $loop_scope->final_actions[] = ScopeAnalyzer::ACTION_LEAVE_SWITCH;
            } else {
                $loop_scope->final_actions[] = ScopeAnalyzer::ACTION_CONTINUE;
            }

            $redefined_vars = $context->getRedefinedVars($loop_scope->loop_parent_context->vars_in_scope);

            if ($loop_scope->redefined_loop_vars === null) {
                $loop_scope->redefined_loop_vars = $redefined_vars;
            } else {
                foreach ($loop_scope->redefined_loop_vars as $redefined_var => $type) {
                    if (!isset($redefined_vars[$redefined_var])) {
                        unset($loop_scope->redefined_loop_vars[$redefined_var]);
                    } else {
                        $loop_scope->redefined_loop_vars[$redefined_var] = Type::combineUnionTypes(
                            $redefined_vars[$redefined_var],
                            $type
                        );
                    }
                }
            }

            foreach ($redefined_vars as $var => $type) {
                $loop_scope->possibly_redefined_loop_vars[$var] = Type::combineUnionTypes(
                    $type,
                    $loop_scope->possibly_redefined_loop_vars[$var] ?? null
                );
            }

            if ($context->finally_scope) {
                foreach ($context->vars_in_scope as $var_id => $type) {
                    if (isset($context->finally_scope->vars_in_scope[$var_id])) {
                        $context->finally_scope->vars_in_scope[$var_id] = Type::combineUnionTypes(
                            $context->finally_scope->vars_in_scope[$var_id],
                            $type,
                            $statements_analyzer->getCodebase()
                        );
                    } else {
                        $context->finally_scope->vars_in_scope[$var_id] = $type;
                        $type->possibly_undefined = true;
                        $type->possibly_undefined_from_try = true;
                    }
                }
            }
        }

        $context->has_returned = true;
    }
}
