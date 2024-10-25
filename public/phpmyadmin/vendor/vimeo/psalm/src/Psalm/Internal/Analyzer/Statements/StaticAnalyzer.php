<?php

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\CodeLocation\DocblockTypeLocation;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Exception\IncorrectDocblockException;
use Psalm\Internal\Analyzer\CommentAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\ReferenceConstraint;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\ImpureStaticVariable;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\MissingDocblockType;
use Psalm\Issue\ReferenceConstraintViolation;
use Psalm\IssueBuffer;
use Psalm\Type;
use UnexpectedValueException;

use function is_string;

class StaticAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Static_ $stmt,
        Context $context
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        if ($context->mutation_free) {
            IssueBuffer::maybeAdd(
                new ImpureStaticVariable(
                    'Cannot use a static variable in a mutation-free context',
                    new CodeLocation($statements_analyzer, $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        }

        foreach ($stmt->vars as $var) {
            if (!is_string($var->var->name)) {
                continue;
            }

            $var_id = '$' . $var->var->name;

            $doc_comment = $stmt->getDocComment();

            $comment_type = null;

            if ($doc_comment && ($parsed_docblock = $statements_analyzer->getParsedDocblock())) {
                $var_comments = [];

                try {
                    $var_comments = $codebase->config->disable_var_parsing
                        ? []
                        : CommentAnalyzer::arrayToDocblocks(
                            $doc_comment,
                            $parsed_docblock,
                            $statements_analyzer->getSource(),
                            $statements_analyzer->getSource()->getAliases(),
                            $statements_analyzer->getSource()->getTemplateTypeMap()
                        );
                } catch (IncorrectDocblockException $e) {
                    IssueBuffer::maybeAdd(
                        new MissingDocblockType(
                            $e->getMessage(),
                            new CodeLocation($statements_analyzer, $var)
                        )
                    );
                } catch (DocblockParseException $e) {
                    IssueBuffer::maybeAdd(
                        new InvalidDocblock(
                            $e->getMessage(),
                            new CodeLocation($statements_analyzer->getSource(), $var)
                        )
                    );
                }

                foreach ($var_comments as $var_comment) {
                    if (!$var_comment->type) {
                        continue;
                    }

                    try {
                        $var_comment_type = TypeExpander::expandUnion(
                            $codebase,
                            $var_comment->type,
                            $context->self,
                            $context->self,
                            $statements_analyzer->getParentFQCLN()
                        );

                        $var_comment_type->setFromDocblock();

                        $var_comment_type->check(
                            $statements_analyzer,
                            new CodeLocation($statements_analyzer->getSource(), $var),
                            $statements_analyzer->getSuppressedIssues()
                        );

                        if ($codebase->alter_code
                            && $var_comment->type_start
                            && $var_comment->type_end
                            && $var_comment->line_number
                        ) {
                            $type_location = new DocblockTypeLocation(
                                $statements_analyzer,
                                $var_comment->type_start,
                                $var_comment->type_end,
                                $var_comment->line_number
                            );

                            $codebase->classlikes->handleDocblockTypeInMigration(
                                $codebase,
                                $statements_analyzer,
                                $var_comment_type,
                                $type_location,
                                $context->calling_method_id
                            );
                        }

                        if (!$var_comment->var_id || $var_comment->var_id === $var_id) {
                            $comment_type = $var_comment_type;
                            continue;
                        }

                        $context->vars_in_scope[$var_comment->var_id] = $var_comment_type;
                    } catch (UnexpectedValueException $e) {
                        IssueBuffer::maybeAdd(
                            new InvalidDocblock(
                                $e->getMessage(),
                                new CodeLocation($statements_analyzer, $var)
                            )
                        );
                    }
                }

                if ($comment_type) {
                    $context->byref_constraints[$var_id] = new ReferenceConstraint($comment_type);
                }
            }

            if ($var->default) {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $var->default, $context) === false) {
                    return;
                }

                if ($comment_type
                    && ($var_default_type = $statements_analyzer->node_data->getType($var->default))
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $var_default_type,
                        $comment_type
                    )
                ) {
                    IssueBuffer::maybeAdd(
                        new ReferenceConstraintViolation(
                            $var_id . ' of type ' . $comment_type->getId() . ' cannot be assigned type '
                                . $var_default_type->getId(),
                            new CodeLocation($statements_analyzer, $var)
                        )
                    );
                }
            }

            if ($context->check_variables) {
                $context->vars_in_scope[$var_id] = $comment_type ? clone $comment_type : Type::getMixed();
                $context->vars_possibly_in_scope[$var_id] = true;
                $context->assigned_var_ids[$var_id] = (int) $stmt->getAttribute('startFilePos');
                $statements_analyzer->byref_uses[$var_id] = true;

                $location = new CodeLocation($statements_analyzer, $var);

                $statements_analyzer->registerVariable(
                    $var_id,
                    $location,
                    $context->branch_point
                );
            }
        }
    }
}
