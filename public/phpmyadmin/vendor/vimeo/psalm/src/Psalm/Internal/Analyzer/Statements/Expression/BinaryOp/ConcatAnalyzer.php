<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\FalseOperand;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\ImpureMethodCall;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyFalseOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_merge;
use function assert;
use function count;
use function reset;
use function strlen;

/**
 * @internal
 */
class ConcatAnalyzer
{
    private const MAX_LITERALS = 64;

    /**
     * @param Union|null $result_type
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        Context $context,
        Union &$result_type = null
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        $left_type = $statements_analyzer->node_data->getType($left);
        $right_type = $statements_analyzer->node_data->getType($right);
        $config = Config::getInstance();

        if ($left_type && $right_type) {
            $result_type = Type::getString();

            if ($left_type->hasMixed() || $right_type->hasMixed()) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                if ($left_type->hasMixed()) {
                    $arg_location = new CodeLocation($statements_analyzer->getSource(), $left);

                    $origin_locations = [];

                    if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph) {
                        foreach ($left_type->parent_nodes as $parent_node) {
                            $origin_locations = array_merge(
                                $origin_locations,
                                $statements_analyzer->data_flow_graph->getOriginLocations($parent_node)
                            );
                        }
                    }

                    $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

                    if ($origin_location && $origin_location->getHash() === $arg_location->getHash()) {
                        $origin_location = null;
                    }

                    IssueBuffer::maybeAdd(
                        new MixedOperand(
                            'Left operand cannot be mixed',
                            $arg_location,
                            $origin_location
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                } else {
                    $arg_location = new CodeLocation($statements_analyzer->getSource(), $right);
                    $origin_locations = [];

                    if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph) {
                        foreach ($right_type->parent_nodes as $parent_node) {
                            $origin_locations = array_merge(
                                $origin_locations,
                                $statements_analyzer->data_flow_graph->getOriginLocations($parent_node)
                            );
                        }
                    }

                    $origin_location = count($origin_locations) === 1 ? reset($origin_locations) : null;

                    if ($origin_location && $origin_location->getHash() === $arg_location->getHash()) {
                        $origin_location = null;
                    }

                    IssueBuffer::maybeAdd(
                        new MixedOperand(
                            'Right operand cannot be mixed',
                            $arg_location,
                            $origin_location
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    );
                }

                return;
            }

            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
            }

            self::analyzeOperand($statements_analyzer, $left, $left_type, 'Left', $context);
            self::analyzeOperand($statements_analyzer, $right, $right_type, 'Right', $context);

            // If both types are specific literals, combine them into new literals
            $literal_concat = false;

            if ($left_type->allSpecificLiterals() && $right_type->allSpecificLiterals()) {
                $left_type_parts = $left_type->getAtomicTypes();
                $right_type_parts = $right_type->getAtomicTypes();
                $combinations = count($left_type_parts) * count($right_type_parts);
                if ($combinations < self::MAX_LITERALS) {
                    $literal_concat = true;
                    $result_type_parts = [];

                    foreach ($left_type->getAtomicTypes() as $left_type_part) {
                        foreach ($right_type->getAtomicTypes() as $right_type_part) {
                            $literal = $left_type_part->value . $right_type_part->value;
                            if (strlen($literal) >= $config->max_string_length) {
                                // Literal too long, use non-literal type instead
                                $literal_concat = false;
                                break 2;
                            }

                            $result_type_parts[] = new TLiteralString($literal);
                        }
                    }

                    if ($literal_concat) {
                        assert(count($result_type_parts) === $combinations);
                        assert(count($result_type_parts) !== 0); // #8163
                        $result_type = new Union($result_type_parts);
                    }
                }
            }

            if (!$literal_concat) {
                $numeric_type = Type::getNumericString();
                $numeric_type->addType(new TInt());
                $numeric_type->addType(new TFloat());
                $left_is_numeric = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $left_type,
                    $numeric_type
                );

                $right_is_numeric = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $right_type,
                    $numeric_type
                );

                $has_numeric_type = $left_is_numeric || $right_is_numeric;

                if ($left_is_numeric) {
                    $right_uint = Type::getPositiveInt();
                    $right_uint->addType(new TLiteralInt(0));
                    $right_is_uint = UnionTypeComparator::isContainedBy(
                        $codebase,
                        $right_type,
                        $right_uint
                    );

                    if ($right_is_uint) {
                        $result_type = Type::getNumericString();
                        return;
                    }
                }

                $lowercase_type = clone $numeric_type;
                $lowercase_type->addType(new TLowercaseString());

                $all_lowercase = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $left_type,
                    $lowercase_type
                ) && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $right_type,
                    $lowercase_type
                );

                $non_empty_string = clone $numeric_type;
                $non_empty_string->addType(new TNonEmptyString());

                $left_non_empty = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $left_type,
                    $non_empty_string
                );

                $right_non_empty = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $right_type,
                    $non_empty_string
                );

                $has_non_empty = $left_non_empty || $right_non_empty;
                $all_non_empty = $left_non_empty && $right_non_empty;

                $has_numeric_and_non_empty = $has_numeric_type && $has_non_empty;

                $all_literals = $left_type->allLiterals() && $right_type->allLiterals();

                if ($has_non_empty) {
                    if ($all_literals) {
                        $result_type = new Union([new TNonEmptyNonspecificLiteralString]);
                    } elseif ($all_lowercase) {
                        $result_type = Type::getNonEmptyLowercaseString();
                    } else {
                        $result_type = $all_non_empty || $has_numeric_and_non_empty ?
                            Type::getNonFalsyString() : Type::getNonEmptyString();
                    }
                } else {
                    if ($all_literals) {
                        $result_type = new Union([new TNonspecificLiteralString]);
                    } elseif ($all_lowercase) {
                        $result_type = Type::getLowercaseString();
                    } else {
                        $result_type = Type::getString();
                    }
                }
            }
        }
    }

    private static function analyzeOperand(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $operand,
        Union $operand_type,
        string $side,
        Context $context
    ): void {
        $codebase = $statements_analyzer->getCodebase();
        $config = Config::getInstance();

        if ($operand_type->isNull()) {
            IssueBuffer::maybeAdd(
                new NullOperand(
                    'Cannot concatenate with a ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand)
                ),
                $statements_analyzer->getSuppressedIssues()
            );

            return;
        }

        if ($operand_type->isFalse()) {
            IssueBuffer::maybeAdd(
                new FalseOperand(
                    'Cannot concatenate with a ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand)
                ),
                $statements_analyzer->getSuppressedIssues()
            );

            return;
        }

        if ($operand_type->isNullable() && !$operand_type->ignore_nullable_issues) {
            IssueBuffer::maybeAdd(
                new PossiblyNullOperand(
                    'Cannot concatenate with a possibly null ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        }

        if ($operand_type->isFalsable() && !$operand_type->ignore_falsable_issues) {
            IssueBuffer::maybeAdd(
                new PossiblyFalseOperand(
                    'Cannot concatenate with a possibly false ' . $operand_type,
                    new CodeLocation($statements_analyzer->getSource(), $operand)
                ),
                $statements_analyzer->getSuppressedIssues()
            );
        }

        $operand_type_match = true;
        $has_valid_operand = false;
        $comparison_result = new TypeComparisonResult();

        foreach ($operand_type->getAtomicTypes() as $operand_type_part) {
            if ($operand_type_part instanceof TTemplateParam && !$operand_type_part->as->isString()) {
                IssueBuffer::maybeAdd(
                    new MixedOperand(
                        "$side operand cannot be a non-string template param",
                        new CodeLocation($statements_analyzer->getSource(), $operand)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );

                return;
            }

            if ($operand_type_part instanceof TNull || $operand_type_part instanceof TFalse) {
                continue;
            }

            $operand_type_part_match = AtomicTypeComparator::isContainedBy(
                $codebase,
                $operand_type_part,
                new TString,
                false,
                false,
                $comparison_result
            );

            $operand_type_match = $operand_type_match && $operand_type_part_match;

            $has_valid_operand = $has_valid_operand || $operand_type_part_match;

            if ($comparison_result->to_string_cast && $config->strict_binary_operands) {
                IssueBuffer::maybeAdd(
                    new ImplicitToStringCast(
                        "$side side of concat op expects string, '$operand_type' provided with a __toString method",
                        new CodeLocation($statements_analyzer->getSource(), $operand)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }

            foreach ($operand_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TNamedObject) {
                    $to_string_method_id = new MethodIdentifier(
                        $atomic_type->value,
                        '__tostring'
                    );

                    if ($codebase->methods->methodExists(
                        $to_string_method_id,
                        $context->calling_method_id,
                        $codebase->collect_locations
                            ? new CodeLocation($statements_analyzer->getSource(), $operand)
                            : null,
                        !$context->collect_initializations
                            && !$context->collect_mutations
                            ? $statements_analyzer
                            : null,
                        $statements_analyzer->getFilePath()
                    )) {
                        try {
                            $storage = $codebase->methods->getStorage($to_string_method_id);
                        } catch (UnexpectedValueException $e) {
                            continue;
                        }

                        if ($context->mutation_free && !$storage->mutation_free) {
                            IssueBuffer::maybeAdd(
                                new ImpureMethodCall(
                                    'Cannot call a possibly-mutating method '
                                        . $atomic_type->value . '::__toString from a pure context',
                                    new CodeLocation($statements_analyzer, $operand)
                                ),
                                $statements_analyzer->getSuppressedIssues()
                            );
                        } elseif ($statements_analyzer->getSource()
                                instanceof FunctionLikeAnalyzer
                            && $statements_analyzer->getSource()->track_mutations
                        ) {
                            $statements_analyzer->getSource()->inferred_has_mutation = true;
                            $statements_analyzer->getSource()->inferred_impure = true;
                        }
                    }
                }
            }
        }

        if (!$operand_type_match
            && (!$comparison_result->scalar_type_match_found || $config->strict_binary_operands)
        ) {
            if ($has_valid_operand) {
                IssueBuffer::maybeAdd(
                    new PossiblyInvalidOperand(
                        'Cannot concatenate with a ' . $operand_type,
                        new CodeLocation($statements_analyzer->getSource(), $operand)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            } else {
                IssueBuffer::maybeAdd(
                    new InvalidOperand(
                        'Cannot concatenate with a ' . $operand_type,
                        new CodeLocation($statements_analyzer->getSource(), $operand)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                );
            }
        }
    }
}
