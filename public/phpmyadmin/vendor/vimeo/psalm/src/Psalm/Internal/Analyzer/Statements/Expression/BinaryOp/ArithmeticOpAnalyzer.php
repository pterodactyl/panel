<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\BinaryOp;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\ArrayAssignmentAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Issue\FalseOperand;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedOperand;
use Psalm\Issue\NullOperand;
use Psalm\Issue\PossiblyFalseOperand;
use Psalm\Issue\PossiblyInvalidOperand;
use Psalm\Issue\PossiblyNullOperand;
use Psalm\Issue\StringIncrement;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_diff_key;
use function array_values;
use function count;
use function is_int;
use function is_numeric;
use function max;
use function min;
use function preg_match;
use function strtolower;

/**
 * @internal
 */
class ArithmeticOpAnalyzer
{
    public static function analyze(
        ?StatementsSource $statements_source,
        NodeDataProvider $nodes,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        PhpParser\Node $parent,
        ?Union &$result_type = null,
        ?Context $context = null
    ): void {
        $codebase = $statements_source ? $statements_source->getCodebase() : null;

        $left_type = $nodes->getType($left);
        $right_type = $nodes->getType($right);
        $config = Config::getInstance();

        if ($left_type && $left_type->isEmpty()) {
            $left_type = $right_type;
        } elseif ($right_type && $right_type->isEmpty()) {
            $right_type = $left_type;
        }

        if ($left_type && $right_type) {
            if ($left_type->isNull()) {
                if ($statements_source && IssueBuffer::accepts(
                    new NullOperand(
                        'Left operand cannot be null',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
                $result_type = Type::getMixed();

                return;
            }

            if ($left_type->isNullable() && !$left_type->ignore_nullable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Left operand cannot be nullable, got ' . $left_type,
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isNull()) {
                if ($statements_source && IssueBuffer::accepts(
                    new NullOperand(
                        'Right operand cannot be null',
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
                $result_type = Type::getMixed();

                return;
            }

            if ($right_type->isNullable() && !$right_type->ignore_nullable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyNullOperand(
                        'Right operand cannot be nullable, got ' . $right_type,
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($left_type->isFalse()) {
                if ($statements_source && IssueBuffer::accepts(
                    new FalseOperand(
                        'Left operand cannot be false',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($left_type->isFalsable() && !$left_type->ignore_falsable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Left operand cannot be falsable, got ' . $left_type,
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($right_type->isFalse()) {
                if ($statements_source && IssueBuffer::accepts(
                    new FalseOperand(
                        'Right operand cannot be false',
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($right_type->isFalsable() && !$right_type->ignore_falsable_issues) {
                if ($statements_source && IssueBuffer::accepts(
                    new PossiblyFalseOperand(
                        'Right operand cannot be falsable, got ' . $right_type,
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $invalid_left_messages = [];
            $invalid_right_messages = [];
            $has_valid_left_operand = false;
            $has_valid_right_operand = false;
            $has_string_increment = false;

            foreach ($left_type->getAtomicTypes() as $left_type_part) {
                foreach ($right_type->getAtomicTypes() as $right_type_part) {
                    $candidate_result_type = self::analyzeOperands(
                        $statements_source,
                        $codebase,
                        $config,
                        $context,
                        $left,
                        $right,
                        $parent,
                        $left_type_part,
                        $right_type_part,
                        $invalid_left_messages,
                        $invalid_right_messages,
                        $has_valid_left_operand,
                        $has_valid_right_operand,
                        $has_string_increment,
                        $result_type
                    );

                    if ($candidate_result_type) {
                        $result_type = $candidate_result_type;
                        return;
                    }
                }
            }

            if ($invalid_left_messages && $statements_source) {
                $first_left_message = $invalid_left_messages[0];

                if ($has_valid_left_operand) {
                    IssueBuffer::maybeAdd(
                        new PossiblyInvalidOperand(
                            $first_left_message,
                            new CodeLocation($statements_source, $left)
                        ),
                        $statements_source->getSuppressedIssues()
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new InvalidOperand(
                            $first_left_message,
                            new CodeLocation($statements_source, $left)
                        ),
                        $statements_source->getSuppressedIssues()
                    );
                }
            }

            if ($invalid_right_messages && $statements_source) {
                $first_right_message = $invalid_right_messages[0];

                if ($has_valid_right_operand) {
                    IssueBuffer::maybeAdd(
                        new PossiblyInvalidOperand(
                            $first_right_message,
                            new CodeLocation($statements_source, $right)
                        ),
                        $statements_source->getSuppressedIssues()
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new InvalidOperand(
                            $first_right_message,
                            new CodeLocation($statements_source, $right)
                        ),
                        $statements_source->getSuppressedIssues()
                    );
                }
            }

            if ($has_string_increment && $statements_source) {
                IssueBuffer::maybeAdd(
                    new StringIncrement(
                        'Possibly unintended string increment',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                );
            }
        }
    }

    /**
     * @param int|float $result
     */
    private static function getNumericalType($result): Union
    {
        if (is_int($result)) {
            return Type::getInt(false, $result);
        }

        return Type::getFloat($result);
    }

    /**
     * @param string[] $invalid_left_messages
     * @param string[] $invalid_right_messages
     */
    private static function analyzeOperands(
        ?StatementsSource $statements_source,
        ?Codebase $codebase,
        Config $config,
        ?Context $context,
        PhpParser\Node\Expr $left,
        PhpParser\Node\Expr $right,
        PhpParser\Node $parent,
        Atomic $left_type_part,
        Atomic $right_type_part,
        array &$invalid_left_messages,
        array &$invalid_right_messages,
        bool &$has_valid_left_operand,
        bool &$has_valid_right_operand,
        bool &$has_string_increment,
        Union &$result_type = null
    ): ?Union {
        if ($left_type_part instanceof TLiteralInt
            && $right_type_part instanceof TLiteralInt
            && (
                //we don't try to do arithmetics on variables in loops
                $context === null
                || $context->inside_loop === false
                || (!$left instanceof PhpParser\Node\Expr\Variable && !$right instanceof PhpParser\Node\Expr\Variable)
            )
        ) {
            // time for some arithmetic!
            $calculated_type = self::arithmeticOperation(
                $parent,
                $left_type_part->value,
                $right_type_part->value,
                true
            );

            if ($calculated_type) {
                $result_type = Type::combineUnionTypes(
                    $calculated_type,
                    $result_type
                );

                $has_valid_left_operand = true;
                $has_valid_right_operand = true;

                return null;
            }
        }

        if ($left_type_part instanceof TNull || $right_type_part instanceof TNull) {
            // null case is handled above
            return null;
        }

        if ($left_type_part instanceof TFalse || $right_type_part instanceof TFalse) {
            // null case is handled above
            return null;
        }

        if ($left_type_part instanceof TString
            && $right_type_part instanceof TInt
            && (
                $parent instanceof PhpParser\Node\Expr\PostInc ||
                $parent instanceof PhpParser\Node\Expr\PreInc
            )
        ) {
            if ($left_type_part instanceof TNumericString ||
                ($left_type_part instanceof TLiteralString && is_numeric($left_type_part->value))
            ) {
                $new_result_type = new Union([new TFloat(), new TInt()]);
                $new_result_type->from_calculation = true;
            } else {
                $new_result_type = Type::getNonEmptyString();
                $has_string_increment = true;
            }

            $result_type = Type::combineUnionTypes($new_result_type, $result_type);

            $has_valid_left_operand = true;
            $has_valid_right_operand = true;

            return null;
        }

        if ($left_type_part instanceof TTemplateParam
            && $right_type_part instanceof TTemplateParam
        ) {
            $combined_type = Type::combineUnionTypes(
                $left_type_part->as,
                $right_type_part->as
            );

            $combined_atomic_types = array_values($combined_type->getAtomicTypes());

            if (count($combined_atomic_types) <= 2) {
                $left_type_part = $combined_atomic_types[0];
                $right_type_part = $combined_atomic_types[1] ?? $combined_atomic_types[0];
            }
        }

        if ($left_type_part instanceof TMixed || $right_type_part instanceof TMixed) {
            if ($statements_source && $codebase && $context) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_source->getFilePath() === $statements_source->getRootFilePath()
                    && (!(($source = $statements_source->getSource())
                            instanceof FunctionLikeAnalyzer)
                        || !$source->getSource() instanceof TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_source->getFilePath());
                }
            }

            if ($left_type_part instanceof TMixed) {
                if ($statements_source && IssueBuffer::accepts(
                    new MixedOperand(
                        'Left operand cannot be mixed',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if ($statements_source && IssueBuffer::accepts(
                    new MixedOperand(
                        'Right operand cannot be mixed',
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($left_type_part instanceof TMixed
                && $left_type_part->from_loop_isset
                && $parent instanceof PhpParser\Node\Expr\AssignOp\Plus
                && !$right_type_part instanceof TMixed
            ) {
                $result_type = Type::combineUnionTypes(new Union([$right_type_part]), $result_type);

                return null;
            }

            $from_loop_isset = (!($left_type_part instanceof TMixed) || $left_type_part->from_loop_isset)
                && (!($right_type_part instanceof TMixed) || $right_type_part->from_loop_isset);

            $result_type = Type::getMixed($from_loop_isset);

            return $result_type;
        }

        if ($left_type_part instanceof TTemplateParam || $right_type_part instanceof TTemplateParam) {
            if ($left_type_part instanceof TTemplateParam
                && !$left_type_part->as->isInt()
                && !$left_type_part->as->isFloat()
            ) {
                if ($statements_source && IssueBuffer::accepts(
                    new MixedOperand(
                        'Left operand cannot be a non-numeric template',
                        new CodeLocation($statements_source, $left)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif ($right_type_part instanceof TTemplateParam
                && !$right_type_part->as->isInt()
                && !$right_type_part->as->isFloat()
            ) {
                if ($statements_source && IssueBuffer::accepts(
                    new MixedOperand(
                        'Right operand cannot be a non-numeric template',
                        new CodeLocation($statements_source, $right)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            return null;
        }

        if ($statements_source && $codebase && $context) {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_source->getFilePath() === $statements_source->getRootFilePath()
                && (!(($parent_source = $statements_source->getSource())
                        instanceof FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_source->getFilePath());
            }
        }

        if ($left_type_part instanceof TArray
            || $right_type_part instanceof TArray
            || $left_type_part instanceof TKeyedArray
            || $right_type_part instanceof TKeyedArray
            || $left_type_part instanceof TList
            || $right_type_part instanceof TList
        ) {
            if ((!$right_type_part instanceof TArray
                    && !$right_type_part instanceof TKeyedArray
                    && !$right_type_part instanceof TList)
                || (!$left_type_part instanceof TArray
                    && !$left_type_part instanceof TKeyedArray
                    && !$left_type_part instanceof TList)
            ) {
                if (!$left_type_part instanceof TArray
                    && !$left_type_part instanceof TKeyedArray
                    && !$left_type_part instanceof TList
                ) {
                    $invalid_left_messages[] = 'Cannot add an array to a non-array ' . $left_type_part;
                } else {
                    $invalid_right_messages[] = 'Cannot add an array to a non-array ' . $right_type_part;
                }

                if ($left_type_part instanceof TArray
                    || $left_type_part instanceof TKeyedArray
                    || $left_type_part instanceof TList
                ) {
                    $has_valid_left_operand = true;
                } elseif ($right_type_part instanceof TArray
                    || $right_type_part instanceof TKeyedArray
                    || $right_type_part instanceof TList
                ) {
                    $has_valid_right_operand = true;
                }

                $result_type = Type::getArray();

                return null;
            }

            $has_valid_right_operand = true;
            $has_valid_left_operand = true;

            if ($left_type_part instanceof TKeyedArray
                && $right_type_part instanceof TKeyedArray
            ) {
                $definitely_existing_mixed_right_properties = array_diff_key(
                    $right_type_part->properties,
                    $left_type_part->properties
                );

                $properties = $left_type_part->properties;

                foreach ($right_type_part->properties as $key => $type) {
                    if (!isset($properties[$key])) {
                        $properties[$key] = $type;
                    } elseif ($properties[$key]->possibly_undefined) {
                        $properties[$key] = Type::combineUnionTypes(
                            $properties[$key],
                            $type,
                            $codebase
                        );

                        $properties[$key]->possibly_undefined = $type->possibly_undefined;
                    }
                }

                if (!$left_type_part->sealed) {
                    foreach ($definitely_existing_mixed_right_properties as $key => $type) {
                        $properties[$key] = Type::combineUnionTypes(Type::getMixed(), $type);
                    }
                }

                $new_keyed_array = new TKeyedArray($properties);
                $new_keyed_array->sealed = $left_type_part->sealed && $right_type_part->sealed;
                $result_type_member = new Union([$new_keyed_array]);
            } else {
                $result_type_member = TypeCombiner::combine(
                    [$left_type_part, $right_type_part],
                    $codebase,
                    true
                );
            }

            $result_type = Type::combineUnionTypes($result_type_member, $result_type, $codebase, true);

            if ($left instanceof PhpParser\Node\Expr\ArrayDimFetch
                && $context
                && $statements_source instanceof StatementsAnalyzer
            ) {
                ArrayAssignmentAnalyzer::updateArrayType(
                    $statements_source,
                    $left,
                    $right,
                    $result_type,
                    $context
                );
            }

            return null;
        }

        if (($left_type_part instanceof TNamedObject && strtolower($left_type_part->value) === 'gmp')
            || ($right_type_part instanceof TNamedObject && strtolower($right_type_part->value) === 'gmp')
        ) {
            if ((($left_type_part instanceof TNamedObject
                        && strtolower($left_type_part->value) === 'gmp')
                    && (($right_type_part instanceof TNamedObject
                            && strtolower($right_type_part->value) === 'gmp')
                        || ($right_type_part->isNumericType() || $right_type_part instanceof TMixed)))
                || (($right_type_part instanceof TNamedObject
                        && strtolower($right_type_part->value) === 'gmp')
                    && (($left_type_part instanceof TNamedObject
                            && strtolower($left_type_part->value) === 'gmp')
                        || ($left_type_part->isNumericType() || $left_type_part instanceof TMixed)))
            ) {
                $result_type = Type::combineUnionTypes(
                    new Union([new TNamedObject('GMP')]),
                    $result_type
                );
            } else {
                if ($statements_source && IssueBuffer::accepts(
                    new InvalidOperand(
                        'Cannot add GMP to non-numeric type',
                        new CodeLocation($statements_source, $parent)
                    ),
                    $statements_source->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            return null;
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Plus
            || $parent instanceof PhpParser\Node\Expr\BinaryOp\Minus
            || $parent instanceof PhpParser\Node\Expr\BinaryOp\Mul
            || $parent instanceof PhpParser\Node\Expr\BinaryOp\Div
            || $parent instanceof PhpParser\Node\Expr\BinaryOp\Mod
            || $parent instanceof PhpParser\Node\Expr\BinaryOp\Pow
        ) {
            $non_decimal_type = null;
            if ($left_type_part instanceof TNamedObject
                && strtolower($left_type_part->value) === "decimal\\decimal"
            ) {
                $non_decimal_type = $right_type_part;
            } elseif ($right_type_part instanceof TNamedObject
                && strtolower($right_type_part->value) === "decimal\\decimal"
            ) {
                $non_decimal_type = $left_type_part;
            }
            if ($non_decimal_type !== null) {
                if ($non_decimal_type instanceof TInt
                    || $non_decimal_type instanceof TNumericString
                    || $non_decimal_type instanceof TNamedObject
                        && strtolower($non_decimal_type->value) === "decimal\\decimal"
                ) {
                    $result_type = Type::combineUnionTypes(
                        new Union([new TNamedObject("Decimal\\Decimal")]),
                        $result_type
                    );
                } else {
                    if ($statements_source) {
                        IssueBuffer::maybeAdd(
                            new InvalidOperand(
                                "Cannot add Decimal\\Decimal to {$non_decimal_type->getId()}",
                                new CodeLocation($statements_source, $parent)
                            ),
                            $statements_source->getSuppressedIssues()
                        );
                    }
                }

                return null;
            }
        }

        if ($left_type_part instanceof TLiteralString) {
            if (preg_match('/^\-?\d+$/', $left_type_part->value)) {
                $left_type_part = new TLiteralInt((int) $left_type_part->value);
            } elseif (preg_match('/^\-?\d?\.\d+$/', $left_type_part->value)) {
                $left_type_part = new TLiteralFloat((float) $left_type_part->value);
            }
        }

        if ($right_type_part instanceof TLiteralString) {
            if (preg_match('/^\-?\d+$/', $right_type_part->value)) {
                $right_type_part = new TLiteralInt((int) $right_type_part->value);
            } elseif (preg_match('/^\-?\d?\.\d+$/', $right_type_part->value)) {
                $right_type_part = new TLiteralFloat((float) $right_type_part->value);
            }
        }

        if ($left_type_part->isNumericType() || $right_type_part->isNumericType()) {
            if (($left_type_part instanceof TNumeric || $right_type_part instanceof TNumeric)
                && ($left_type_part->isNumericType() && $right_type_part->isNumericType())
            ) {
                if ($config->strict_binary_operands) {
                    if ($statements_source && IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot process different numeric types together in strict binary operands mode, '.
                            'please cast explicitly',
                            new CodeLocation($statements_source, $parent)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $new_result_type = Type::getInt();
                } else {
                    $new_result_type = new Union([new TFloat(), new TInt()]);
                }

                $result_type = Type::combineUnionTypes($new_result_type, $result_type);

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return null;
            }

            if ($left_type_part instanceof TIntRange && $right_type_part instanceof TIntRange) {
                self::analyzeOperandsBetweenIntRange($parent, $result_type, $left_type_part, $right_type_part);
                return null;
            }

            if (($left_type_part instanceof TIntRange && $right_type_part instanceof TInt) ||
                ($left_type_part instanceof TInt && $right_type_part instanceof TIntRange)
            ) {
                self::analyzeOperandsBetweenIntRangeAndInt(
                    $parent,
                    $result_type,
                    $left_type_part,
                    $right_type_part
                );
                return null;
            }

            if ($left_type_part instanceof TInt && $right_type_part instanceof TInt) {
                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Div) {
                    $result_type = new Union([new TInt(), new TFloat()]);
                } else {
                    $left_is_positive = $left_type_part instanceof TPositiveInt
                        || ($left_type_part instanceof TLiteralInt && $left_type_part->value > 0);

                    $right_is_positive = $right_type_part instanceof TPositiveInt
                        || ($right_type_part instanceof TLiteralInt && $right_type_part->value > 0);

                    if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Minus) {
                        $always_positive = false;
                    } elseif ($left_is_positive && $right_is_positive) {
                        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
                            || $parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd
                            || $parent instanceof PhpParser\Node\Expr\BinaryOp\ShiftLeft
                            || $parent instanceof PhpParser\Node\Expr\BinaryOp\ShiftRight
                        ) {
                            $always_positive = false;
                        } else {
                            $always_positive = true;
                        }
                    } elseif ($parent instanceof PhpParser\Node\Expr\BinaryOp\Plus
                        && ($left_type_part instanceof TLiteralInt && $left_type_part->value === 0)
                        && $right_is_positive
                    ) {
                        $always_positive = true;
                    } elseif ($parent instanceof PhpParser\Node\Expr\BinaryOp\Plus
                        && ($right_type_part instanceof TLiteralInt && $right_type_part->value === 0)
                        && $left_is_positive
                    ) {
                        $always_positive = true;
                    } else {
                        $always_positive = false;
                    }

                    if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                        if ($right_type_part instanceof TLiteralInt) {
                            $literal_value_max = $right_type_part->value - 1;
                            if ($always_positive) {
                                $result_type = new Union([new TIntRange(0, $literal_value_max)]);
                            } else {
                                $result_type = new Union(
                                    [new TIntRange(-$literal_value_max, $literal_value_max)]
                                );
                            }
                        } else {
                            if ($always_positive) {
                                $result_type = new Union([
                                    new TPositiveInt(),
                                    new TLiteralInt(0)
                                ]);
                            } else {
                                $result_type = Type::getInt();
                            }
                        }
                    } else {
                        $result_type = Type::combineUnionTypes(
                            $always_positive ? Type::getPositiveInt(true) : Type::getInt(true),
                            $result_type
                        );
                    }
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return null;
            }

            if ($left_type_part instanceof TFloat && $right_type_part instanceof TFloat) {
                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } else {
                    $result_type = Type::combineUnionTypes(Type::getFloat(), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return null;
            }

            if (($left_type_part instanceof TFloat && $right_type_part instanceof TInt)
                || ($left_type_part instanceof TInt && $right_type_part instanceof TFloat)
            ) {
                if ($config->strict_binary_operands) {
                    if ($statements_source && IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot process ints and floats in strict binary operands mode, '.
                            'please cast explicitly',
                            new CodeLocation($statements_source, $parent)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } else {
                    $result_type = Type::combineUnionTypes(Type::getFloat(), $result_type);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return null;
            }

            if ($left_type_part->isNumericType() && $right_type_part->isNumericType()) {
                if ($config->strict_binary_operands) {
                    if ($statements_source && IssueBuffer::accepts(
                        new InvalidOperand(
                            'Cannot process numeric types together in strict operands mode, '.
                            'please cast explicitly',
                            new CodeLocation($statements_source, $parent)
                        ),
                        $statements_source->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
                    $result_type = Type::getInt();
                } else {
                    $result_type = new Union([new TInt, new TFloat]);
                }

                $has_valid_right_operand = true;
                $has_valid_left_operand = true;

                return null;
            }

            if (!$left_type_part->isNumericType()) {
                $invalid_left_messages[] = 'Cannot perform a numeric operation with a non-numeric type '
                    . $left_type_part;
                $has_valid_right_operand = true;
            } else {
                $invalid_right_messages[] = 'Cannot perform a numeric operation with a non-numeric type '
                    . $right_type_part;
                $has_valid_left_operand = true;
            }
        } else {
            $invalid_left_messages[] =
                'Cannot perform a numeric operation with non-numeric types ' . $left_type_part
                    . ' and ' . $right_type_part;
        }

        return null;
    }

    /**
     * @param PhpParser\Node $operation
     * @param float|int      $operand1
     * @param float|int      $operand2
     */
    public static function arithmeticOperation(
        PhpParser\Node $operation,
        $operand1,
        $operand2,
        bool $allow_float_result
    ): ?Union {
        if ($operation instanceof PhpParser\Node\Expr\BinaryOp\Plus) {
            $result = $operand1 + $operand2;
        } elseif ($operation instanceof PhpParser\Node\Expr\BinaryOp\Minus) {
            $result = $operand1 - $operand2;
        } elseif ($operation instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
            if ($operand2 === 0) {
                return Type::getEmpty();
            }

            $result = $operand1 % $operand2;
        } elseif ($operation instanceof PhpParser\Node\Expr\BinaryOp\Mul) {
            $result = $operand1 * $operand2;
        } elseif ($operation instanceof PhpParser\Node\Expr\BinaryOp\Pow) {
            $result = $operand1 ** $operand2;
        } elseif ($operation instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr) {
            $result = $operand1 | $operand2;
        } elseif ($operation instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd) {
            $result = $operand1 & $operand2;
        } elseif ($operation instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor) {
            $result = $operand1 ^ $operand2;
        } elseif ($operation instanceof PhpParser\Node\Expr\BinaryOp\ShiftLeft) {
            $result = $operand1 << $operand2;
        } elseif ($operation instanceof PhpParser\Node\Expr\BinaryOp\ShiftRight) {
            $result = $operand1 >> $operand2;
        } elseif ($operation instanceof PhpParser\Node\Expr\BinaryOp\Div) {
            if ($operand2 === 0) {
                return Type::getEmpty();
            }

            $result = $operand1 / $operand2;
        } else {
            return null;
        }

        $calculated_type = self::getNumericalType($result);
        if (!$allow_float_result && $calculated_type->isFloat()) {
            return null;
        }

        return $calculated_type;
    }

    private static function analyzeOperandsBetweenIntRange(
        PhpParser\Node $parent,
        ?Union &$result_type,
        TIntRange $left_type_part,
        TIntRange $right_type_part
    ): void {
        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Div) {
            //can't assume an int range will stay int after division
            $result_type = Type::combineUnionTypes(
                new Union([new TInt(), new TFloat()]),
                $result_type
            );
            return;
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mod) {
            self::analyzeModBetweenIntRange($result_type, $left_type_part, $right_type_part);
            return;
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseAnd ||
            $parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseOr ||
            $parent instanceof PhpParser\Node\Expr\BinaryOp\BitwiseXor
        ) {
            //really complex to calculate
            $result_type = Type::combineUnionTypes(
                Type::getInt(),
                $result_type
            );
            return;
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\ShiftLeft ||
            $parent instanceof PhpParser\Node\Expr\BinaryOp\ShiftRight
        ) {
            //really complex to calculate
            $result_type = Type::combineUnionTypes(
                new Union([new TInt()]),
                $result_type
            );
            return;
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Mul) {
            self::analyzeMulBetweenIntRange($parent, $result_type, $left_type_part, $right_type_part);
            return;
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Pow) {
            self::analyzePowBetweenIntRange($result_type, $left_type_part, $right_type_part);
            return;
        }

        if ($parent instanceof PhpParser\Node\Expr\BinaryOp\Minus) {
            //for Minus, we have to assume the min is the min from first range minus the max from the second
            $min_operand1 = $left_type_part->min_bound;
            $min_operand2 = $right_type_part->max_bound;
            //and the max is the max from first range minus the min from the second
            $max_operand1 = $left_type_part->max_bound;
            $max_operand2 = $right_type_part->min_bound;
        } else {
            $min_operand1 = $left_type_part->min_bound;
            $min_operand2 = $right_type_part->min_bound;

            $max_operand1 = $left_type_part->max_bound;
            $max_operand2 = $right_type_part->max_bound;
        }

        $calculated_min_type = null;
        if ($min_operand1 !== null && $min_operand2 !== null) {
            // when there are two valid numbers, make any operation
            $calculated_min_type = self::arithmeticOperation(
                $parent,
                $min_operand1,
                $min_operand2,
                false
            );
        }

        $calculated_max_type = null;
        if ($max_operand1 !== null && $max_operand2 !== null) {
            // when there are two valid numbers, make any operation
            $calculated_max_type = self::arithmeticOperation(
                $parent,
                $max_operand1,
                $max_operand2,
                false
            );
        }

        $min_value = $calculated_min_type !== null ? $calculated_min_type->getSingleIntLiteral()->value : null;
        $max_value = $calculated_max_type !== null ? $calculated_max_type->getSingleIntLiteral()->value : null;

        $new_result_type = new Union([new TIntRange($min_value, $max_value)]);

        $result_type = Type::combineUnionTypes($new_result_type, $result_type);
    }

    /**
     * @param TIntRange|TInt $left_type_part
     * @param TIntRange|TInt $right_type_part
     */
    private static function analyzeOperandsBetweenIntRangeAndInt(
        PhpParser\Node $parent,
        ?Union &$result_type,
        Atomic $left_type_part,
        Atomic $right_type_part
    ): void {
        if (!$left_type_part instanceof TIntRange) {
            $left_type_part = TIntRange::convertToIntRange($left_type_part);
        }
        if (!$right_type_part instanceof TIntRange) {
            $right_type_part = TIntRange::convertToIntRange($right_type_part);
        }

        self::analyzeOperandsBetweenIntRange($parent, $result_type, $left_type_part, $right_type_part);
    }

    private static function analyzeMulBetweenIntRange(
        PhpParser\Node\Expr\BinaryOp\Mul $parent,
        ?Union &$result_type,
        TIntRange $left_type_part,
        TIntRange $right_type_part
    ): void {
        //Mul is a special case because of double negatives. We can only infer when we know both signs strictly
        if ($right_type_part->min_bound !== null
            && $right_type_part->max_bound !== null
            && $left_type_part->min_bound !== null
            && $left_type_part->max_bound !== null
        ) {
            //everything is known, we can do calculations
            //[ x_1 , x_2 ] ⋆ [ y_1 , y_2 ] =
            //      [
            //          min(x_1 ⋆ y_1 , x_1 ⋆ y_2 , x_2 ⋆ y_1 , x_2 ⋆ y_2),
            //          max(x_1 ⋆ y_1 , x_1 ⋆ y_2 , x_2 ⋆ y_1 , x_2 ⋆ y_2)
            //      ]
            $x_1 = $right_type_part->min_bound;
            $x_2 = $right_type_part->max_bound;
            $y_1 = $left_type_part->min_bound;
            $y_2 = $left_type_part->max_bound;
            $min_value = min($x_1 * $y_1, $x_1 * $y_2, $x_2 * $y_1, $x_2 * $y_2);
            $max_value = max($x_1 * $y_1, $x_1 * $y_2, $x_2 * $y_1, $x_2 * $y_2);

            $new_result_type = new Union([new TIntRange($min_value, $max_value)]);
        } elseif ($right_type_part->isPositiveOrZero() && $left_type_part->isPositiveOrZero()) {
            // both operands are positive, result will be only positive
            $min_operand1 = $left_type_part->min_bound;
            $min_operand2 = $right_type_part->min_bound;

            $max_operand1 = $left_type_part->max_bound;
            $max_operand2 = $right_type_part->max_bound;

            $calculated_min_type = null;
            if ($min_operand1 !== null && $min_operand2 !== null) {
                // when there are two valid numbers, make any operation
                $calculated_min_type = self::arithmeticOperation(
                    $parent,
                    $min_operand1,
                    $min_operand2,
                    false
                );
            }

            $calculated_max_type = null;
            if ($max_operand1 !== null && $max_operand2 !== null) {
                // when there are two valid numbers, make any operation
                $calculated_max_type = self::arithmeticOperation(
                    $parent,
                    $max_operand1,
                    $max_operand2,
                    false
                );
            }

            $min_value = $calculated_min_type !== null ? $calculated_min_type->getSingleIntLiteral()->value : null;
            $max_value = $calculated_max_type !== null ? $calculated_max_type->getSingleIntLiteral()->value : null;

            $new_result_type = new Union([new TIntRange($min_value, $max_value)]);
        } elseif ($right_type_part->isPositiveOrZero() && $left_type_part->isNegativeOrZero()) {
            // one operand is negative, result will be negative and we have to check min vs max
            $min_operand1 = $left_type_part->max_bound;
            $min_operand2 = $right_type_part->min_bound;

            $max_operand1 = $left_type_part->min_bound;
            $max_operand2 = $right_type_part->max_bound;

            $calculated_min_type = null;
            if ($min_operand1 !== null && $min_operand2 !== null) {
                // when there are two valid numbers, make any operation
                $calculated_min_type = self::arithmeticOperation(
                    $parent,
                    $min_operand1,
                    $min_operand2,
                    false
                );
            }

            $calculated_max_type = null;
            if ($max_operand1 !== null && $max_operand2 !== null) {
                // when there are two valid numbers, make any operation
                $calculated_max_type = self::arithmeticOperation(
                    $parent,
                    $max_operand1,
                    $max_operand2,
                    false
                );
            }

            $min_value = $calculated_min_type !== null ? $calculated_min_type->getSingleIntLiteral()->value : null;
            $max_value = $calculated_max_type !== null ? $calculated_max_type->getSingleIntLiteral()->value : null;

            if ($min_value > $max_value) {
                [$min_value, $max_value] = [$max_value, $min_value];
            }

            $new_result_type = new Union([new TIntRange($min_value, $max_value)]);
        } elseif ($right_type_part->isNegativeOrZero() && $left_type_part->isPositiveOrZero()) {
            // one operand is negative, result will be negative and we have to check min vs max
            $min_operand1 = $left_type_part->min_bound;
            $min_operand2 = $right_type_part->max_bound;

            $max_operand1 = $left_type_part->max_bound;
            $max_operand2 = $right_type_part->min_bound;

            $calculated_min_type = null;
            if ($min_operand1 !== null && $min_operand2 !== null) {
                // when there are two valid numbers, make any operation
                $calculated_min_type = self::arithmeticOperation(
                    $parent,
                    $min_operand1,
                    $min_operand2,
                    false
                );
            }

            $calculated_max_type = null;
            if ($max_operand1 !== null && $max_operand2 !== null) {
                // when there are two valid numbers, make any operation
                $calculated_max_type = self::arithmeticOperation(
                    $parent,
                    $max_operand1,
                    $max_operand2,
                    false
                );
            }

            $min_value = $calculated_min_type !== null ? $calculated_min_type->getSingleIntLiteral()->value : null;
            $max_value = $calculated_max_type !== null ? $calculated_max_type->getSingleIntLiteral()->value : null;

            if ($min_value > $max_value) {
                [$min_value, $max_value] = [$max_value, $min_value];
            }

            $new_result_type = new Union([new TIntRange($min_value, $max_value)]);
        } elseif ($right_type_part->isNegativeOrZero() && $left_type_part->isNegativeOrZero()) {
            // both operand are negative, result will be positive
            $min_operand1 = $left_type_part->max_bound;
            $min_operand2 = $right_type_part->max_bound;

            $max_operand1 = $left_type_part->min_bound;
            $max_operand2 = $right_type_part->min_bound;

            $calculated_min_type = null;
            if ($min_operand1 !== null && $min_operand2 !== null) {
                // when there are two valid numbers, make any operation
                $calculated_min_type = self::arithmeticOperation(
                    $parent,
                    $min_operand1,
                    $min_operand2,
                    false
                );
            }

            $calculated_max_type = null;
            if ($max_operand1 !== null && $max_operand2 !== null) {
                // when there are two valid numbers, make any operation
                $calculated_max_type = self::arithmeticOperation(
                    $parent,
                    $max_operand1,
                    $max_operand2,
                    false
                );
            }

            $min_value = $calculated_min_type !== null ? $calculated_min_type->getSingleIntLiteral()->value : null;
            $max_value = $calculated_max_type !== null ? $calculated_max_type->getSingleIntLiteral()->value : null;

            $new_result_type = new Union([new TIntRange($min_value, $max_value)]);
        } else {
            $new_result_type = Type::getInt(true);
        }

        $result_type = Type::combineUnionTypes($new_result_type, $result_type);
    }

    private static function analyzePowBetweenIntRange(
        ?Union &$result_type,
        TIntRange $left_type_part,
        TIntRange $right_type_part
    ): void {
        //If Pow first operand is negative, the result could be positive or negative, else it will be positive
        //If Pow second operand is negative, the result will be float, if it's 0, it will be 1/-1, else positive
        if ($left_type_part->isPositive()) {
            if ($right_type_part->isPositive()) {
                $new_result_type = new Union([new TIntRange(1, null)]);
            } elseif ($right_type_part->isNegative()) {
                $new_result_type = Type::getFloat();
            } elseif ($right_type_part->min_bound === 0 && $right_type_part->max_bound === 0) {
                $new_result_type = Type::getInt(true, 1);
            } else {
                //$right_type_part may be a mix of positive, negative and 0
                $new_result_type = new Union([new TInt(), new TFloat()]);
            }
        } elseif ($left_type_part->isNegative()) {
            if ($right_type_part->isPositive()) {
                if ($right_type_part->min_bound === $right_type_part->max_bound) {
                    if ($right_type_part->max_bound % 2 === 0) {
                        $new_result_type = new Union([new TIntRange(1, null)]);
                    } else {
                        $new_result_type = new Union([new TIntRange(null, -1)]);
                    }
                } else {
                    $new_result_type = Type::getInt(true);
                }
            } elseif ($right_type_part->isNegative()) {
                $new_result_type = Type::getFloat();
            } elseif ($right_type_part->min_bound === 0 && $right_type_part->max_bound === 0) {
                $new_result_type = Type::getInt(true, -1);
            } else {
                //$right_type_part may be a mix of positive, negative and 0
                $new_result_type = new Union([new TInt(), new TFloat()]);
            }
        } elseif ($left_type_part->min_bound === 0 && $left_type_part->max_bound === 0) {
            if ($right_type_part->isPositive()) {
                $new_result_type = Type::getInt(true, 0);
            } elseif ($right_type_part->min_bound === 0 && $right_type_part->max_bound === 0) {
                $new_result_type = Type::getInt(true, 1);
            } else {
                //technically could be a float(INF)...
                $new_result_type = Type::getEmpty();
            }
        } else {
            //$left_type_part may be a mix of positive, negative and 0
            if ($right_type_part->isPositive()) {
                if ($right_type_part->min_bound === $right_type_part->max_bound
                    && $right_type_part->max_bound % 2 === 0
                ) {
                    $new_result_type = new Union([new TIntRange(1, null)]);
                } else {
                    $new_result_type = Type::getInt(true);
                }
            } elseif ($right_type_part->isNegative()) {
                $new_result_type = Type::getFloat();
            } elseif ($right_type_part->min_bound === 0 && $right_type_part->max_bound === 0) {
                $new_result_type = Type::getInt(true, 1);
            } else {
                //$left_type_part may be a mix of positive, negative and 0
                $new_result_type = new Union([new TInt(), new TFloat()]);
            }
        }

        $result_type = Type::combineUnionTypes($new_result_type, $result_type);
    }

    private static function analyzeModBetweenIntRange(
        ?Union &$result_type,
        TIntRange $left_type_part,
        TIntRange $right_type_part
    ): void {
        //result of Mod is not directly dependant on the bounds of the range
        if ($right_type_part->min_bound !== null && $right_type_part->min_bound === $right_type_part->max_bound) {
            //if the second operand is a literal, we can be pretty detailed
            if ($right_type_part->max_bound === 0) {
                $new_result_type = Type::getEmpty();
            } else {
                if ($left_type_part->isPositiveOrZero()) {
                    if ($right_type_part->isPositive()) {
                        $max = $right_type_part->min_bound - 1;
                        $new_result_type = new Union([new TIntRange(0, $max)]);
                    } else {
                        $max = $right_type_part->min_bound + 1;
                        $new_result_type = new Union([new TIntRange($max, 0)]);
                    }
                } elseif ($left_type_part->isNegativeOrZero()) {
                    if ($right_type_part->isPositive()) {
                        $max = $right_type_part->min_bound - 1;
                        $new_result_type = new Union([new TIntRange(-$max, 0)]);
                    } else {
                        $max = $right_type_part->min_bound + 1;
                        $new_result_type = new Union([new TIntRange(-$max, 0)]);
                    }
                } else {
                    if ($right_type_part->isPositive()) {
                        $max = $right_type_part->min_bound - 1;
                    } else {
                        $max = -$right_type_part->min_bound - 1;
                    }
                    $new_result_type = new Union([new TIntRange(-$max, $max)]);
                }
            }
        } elseif ($right_type_part->isPositive()) {
            if ($left_type_part->isPositiveOrZero()) {
                if ($right_type_part->max_bound !== null) {
                    //we now that the result will be a range between 0 and $right->max - 1
                    $new_result_type = new Union(
                        [new TIntRange(0, $right_type_part->max_bound - 1)]
                    );
                } else {
                    $new_result_type = new Union([new TIntRange(0, null)]);
                }
            } elseif ($left_type_part->isNegativeOrZero()) {
                $new_result_type = new Union([new TIntRange(null, 0)]);
            } else {
                $new_result_type = Type::getInt(true);
            }
        } elseif ($right_type_part->isNegative()) {
            if ($left_type_part->isPositiveOrZero()) {
                $new_result_type = new Union([new TIntRange(null, 0)]);
            } elseif ($left_type_part->isNegativeOrZero()) {
                $new_result_type = new Union([new TIntRange(null, 0)]);
            } else {
                $new_result_type = Type::getInt(true);
            }
        } else {
            $new_result_type = Type::getInt(true);
        }

        $result_type = Type::combineUnionTypes(
            $new_result_type,
            $result_type
        );
    }
}
