<?php

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function count;
use function explode;
use function get_class;
use function strpos;
use function strtolower;
use function substr;

class NegatedAssertionReconciler extends Reconciler
{
    /**
     * @param  array<string, array<string, Union>> $template_type_map
     * @param  string[]   $suppressed_issues
     * @param  Reconciler::RECONCILIATION_*      $failed_reconciliation
     */
    public static function reconcile(
        StatementsAnalyzer $statements_analyzer,
        string $assertion,
        bool $is_strict_equality,
        bool $is_loose_equality,
        Union $existing_var_type,
        array $template_type_map,
        string $old_var_type_string,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $inside_loop
    ): Union {
        $is_equality = $is_strict_equality || $is_loose_equality;

        // this is a specific value comparison type that cannot be negated
        if ($is_equality && $bracket_pos = strpos($assertion, '(')) {
            if ($existing_var_type->hasMixed()) {
                return $existing_var_type;
            }

            return self::handleLiteralNegatedEquality(
                $statements_analyzer,
                $assertion,
                $bracket_pos,
                $existing_var_type,
                $old_var_type_string,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $is_strict_equality
            );
        }

        if ($is_equality && $assertion === 'positive-numeric') {
            return $existing_var_type;
        }


        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($assertion === 'false' && isset($existing_var_atomic_types['bool'])) {
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TTrue);
        } elseif ($assertion === 'true' && isset($existing_var_atomic_types['bool'])) {
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TFalse);
        } else {
            $simple_negated_type = SimpleNegatedAssertionReconciler::reconcile(
                $statements_analyzer->getCodebase(),
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                $inside_loop
            );

            if ($simple_negated_type) {
                return $simple_negated_type;
            }
        }

        if ($assertion === 'iterable' || $assertion === 'countable') {
            $existing_var_type->removeType('array');
        }

        if (!$is_equality
            && isset($existing_var_atomic_types['int'])
            && $existing_var_type->from_calculation
            && ($assertion === 'int' || $assertion === 'float')
        ) {
            $existing_var_type->removeType($assertion);

            if ($assertion === 'int') {
                $existing_var_type->addType(new TFloat);
            } else {
                $existing_var_type->addType(new TInt);
            }

            $existing_var_type->from_calculation = false;

            return $existing_var_type;
        }

        if (!$is_equality
            && ($assertion === 'DateTime' || $assertion === 'DateTimeImmutable')
            && isset($existing_var_atomic_types['DateTimeInterface'])
        ) {
            $existing_var_type->removeType('DateTimeInterface');

            if ($assertion === 'DateTime') {
                $existing_var_type->addType(new TNamedObject('DateTimeImmutable'));
            } else {
                $existing_var_type->addType(new TNamedObject('DateTime'));
            }

            return $existing_var_type;
        }

        if (strtolower($assertion) === 'traversable'
            && isset($existing_var_atomic_types['iterable'])
        ) {
            /** @var TIterable */
            $iterable = $existing_var_atomic_types['iterable'];
            $existing_var_type->removeType('iterable');
            $existing_var_type->addType(new TArray(
                [
                    $iterable->type_params[0]->hasMixed()
                        ? Type::getArrayKey()
                        : clone $iterable->type_params[0],
                    clone $iterable->type_params[1],
                ]
            ));
        } elseif (strtolower($assertion) === 'int'
            && isset($existing_var_type->getAtomicTypes()['array-key'])
        ) {
            $existing_var_type->removeType('array-key');
            $existing_var_type->addType(new TString);
        } elseif (strpos($assertion, 'getclass-') === 0) {
            $assertion = substr($assertion, 9);
        } elseif ($existing_var_type->isSingle()
            && $existing_var_type->hasNamedObjectType()
            && isset($existing_var_type->getAtomicTypes()[$assertion])
        ) {
            // checking if two types share a common parent is not enough to guarantee childs are instanceof each other
            // fall through
        } elseif (!$is_equality) {
            $codebase = $statements_analyzer->getCodebase();

            // if there wasn't a direct hit, go deeper, eliminating subtypes
            if (!$existing_var_type->removeType($assertion)) {
                foreach ($existing_var_type->getAtomicTypes() as $part_name => $existing_var_type_part) {
                    if (!$existing_var_type_part->isObjectType() || strpos($assertion, '-')) {
                        continue;
                    }

                    $assertion_type = Type::parseString($assertion, null, $template_type_map);

                    if (!$assertion_type->isSingle()) {
                        continue;
                    }

                    $new_type_part = $assertion_type->getSingleAtomic();

                    if (!$new_type_part instanceof TNamedObject) {
                        continue;
                    }

                    if (AtomicTypeComparator::isContainedBy(
                        $codebase,
                        $existing_var_type_part,
                        $new_type_part,
                        false,
                        false
                    )) {
                        $existing_var_type->removeType($part_name);
                    } elseif (AtomicTypeComparator::isContainedBy(
                        $codebase,
                        $new_type_part,
                        $existing_var_type_part,
                        false,
                        false
                    )) {
                        $existing_var_type->different = true;
                    }
                }
            }
        }

        if ($is_strict_equality
            && $assertion !== 'isset'
            && ($key !== '$this'
                || !($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer))
        ) {
            $assertion = Type::parseString($assertion, null, $template_type_map);

            if ($key
                && $code_location
                && !UnionTypeComparator::canExpressionTypesBeIdentical(
                    $statements_analyzer->getCodebase(),
                    $existing_var_type,
                    $assertion
                )
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!=' . $assertion,
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($existing_var_type->isUnionEmpty()) {
            if ($key !== '$this'
                || !($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
            ) {
                if ($key && $code_location && !$is_equality) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        '!' . $assertion,
                        false,
                        $negated,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }

            $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

            return new Union([new TEmptyMixed]);
        }

        return $existing_var_type;
    }

    /**
     * @param  string[]   $suppressed_issues
     *
     */
    private static function handleLiteralNegatedEquality(
        StatementsAnalyzer $statements_analyzer,
        string $assertion,
        int $bracket_pos,
        Union $existing_var_type,
        string $old_var_type_string,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        bool $is_strict_equality
    ): Union {
        $scalar_type = substr($assertion, 0, $bracket_pos);

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $did_remove_type = false;
        $did_match_literal_type = false;

        $scalar_var_type = null;

        if ($scalar_type === 'int') {
            if ($existing_var_type->hasInt()) {
                if ($existing_int_types = $existing_var_type->getLiteralInts()) {
                    if (!$existing_var_type->hasPositiveInt()) {
                        $did_match_literal_type = true;
                    }

                    if (isset($existing_int_types[$assertion])) {
                        $existing_var_type->removeType($assertion);

                        $did_remove_type = true;
                    }
                }
            } else {
                $scalar_value = substr($assertion, $bracket_pos + 1, -1);
                $scalar_var_type = Type::getInt(false, (int) $scalar_value);
            }
        } elseif ($scalar_type === 'string'
            || $scalar_type === 'class-string'
            || $scalar_type === 'interface-string'
            || $scalar_type === 'trait-string'
            || $scalar_type === 'callable-string'
        ) {
            if ($existing_var_type->hasString()) {
                if ($existing_string_types = $existing_var_type->getLiteralStrings()) {
                    $did_match_literal_type = true;

                    if (isset($existing_string_types[$assertion])) {
                        $existing_var_type->removeType($assertion);

                        $did_remove_type = true;
                    }
                } elseif ($assertion === 'string()') {
                    $existing_var_type->addType(new TNonEmptyString());
                }
            } elseif ($scalar_type === 'string') {
                $scalar_value = substr($assertion, $bracket_pos + 1, -1);
                $scalar_var_type = Type::getString($scalar_value);
            }
        } elseif ($scalar_type === 'float') {
            if ($existing_var_type->hasFloat()) {
                if ($existing_float_types = $existing_var_type->getLiteralFloats()) {
                    $did_match_literal_type = true;

                    if (isset($existing_float_types[$assertion])) {
                        $existing_var_type->removeType($assertion);

                        $did_remove_type = true;
                    }
                }
            } else {
                $scalar_value = substr($assertion, $bracket_pos + 1, -1);
                $scalar_var_type = Type::getFloat((float) $scalar_value);
            }
        } elseif ($scalar_type === 'enum') {
            [$fq_enum_name, $case_name] = explode('::', substr($assertion, $bracket_pos + 1, -1));

            foreach ($existing_var_type->getAtomicTypes() as $atomic_key => $atomic_type) {
                if (get_class($atomic_type) === TNamedObject::class
                    && $atomic_type->value === $fq_enum_name
                ) {
                    $codebase = $statements_analyzer->getCodebase();

                    $enum_storage = $codebase->classlike_storage_provider->get($fq_enum_name);

                    if (!$enum_storage->is_enum || !$enum_storage->enum_cases) {
                        $scalar_var_type = new Union([new TEnumCase($fq_enum_name, $case_name)]);
                    } else {
                        $existing_var_type->removeType($atomic_type->getKey());
                        $did_remove_type = true;

                        foreach ($enum_storage->enum_cases as $alt_case_name => $_) {
                            if ($alt_case_name === $case_name) {
                                continue;
                            }

                            $existing_var_type->addType(new TEnumCase($fq_enum_name, $alt_case_name));
                        }
                    }
                } elseif ($atomic_type instanceof TEnumCase
                    && $atomic_type->value === $fq_enum_name
                    && $atomic_type->case_name !== $case_name
                ) {
                    $did_match_literal_type = true;
                } elseif ($atomic_key === $assertion) {
                    $existing_var_type->removeType($assertion);
                    $did_remove_type = true;
                }
            }
        }

        if ($key && $code_location) {
            if ($did_match_literal_type
                && (!$did_remove_type || count($existing_var_atomic_types) === 1)
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!' . $assertion,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            } elseif ($scalar_var_type
                && $is_strict_equality
                && ($key !== '$this'
                    || !($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer))
            ) {
                if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                    $statements_analyzer->getCodebase(),
                    $existing_var_type,
                    $scalar_var_type
                )) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        '!=' . $assertion,
                        true,
                        $negated,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }
        }

        return $existing_var_type;
    }
}
