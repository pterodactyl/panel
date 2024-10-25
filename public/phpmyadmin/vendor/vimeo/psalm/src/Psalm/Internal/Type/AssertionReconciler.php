<?php

namespace Psalm\Internal\Type;

use Exception;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\TypeDoesNotContainNull;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEnumCase;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function array_intersect_key;
use function array_merge;
use function count;
use function explode;
use function get_class;
use function is_string;
use function strpos;
use function substr;

class AssertionReconciler extends Reconciler
{
    /**
     * Reconciles types
     *
     * think of this as a set of functions e.g. empty(T), notEmpty(T), null(T), notNull(T) etc. where
     *  - empty(Object) => null,
     *  - empty(bool) => false,
     *  - notEmpty(Object|null) => Object,
     *  - notEmpty(Object|false) => Object
     *
     * @param   string[]            $suppressed_issues
     * @param   array<string, array<string, Union>> $template_type_map
     * @param-out Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    public static function reconcile(
        string $assertion,
        ?Union $existing_var_type,
        ?string $key,
        StatementsAnalyzer $statements_analyzer,
        bool $inside_loop,
        array $template_type_map,
        ?CodeLocation $code_location = null,
        array $suppressed_issues = [],
        ?int &$failed_reconciliation = Reconciler::RECONCILIATION_OK,
        bool $negated = false
    ): Union {
        $codebase = $statements_analyzer->getCodebase();

        $is_strict_equality = false;
        $is_loose_equality = false;
        $is_equality = false;
        $is_negation = false;
        $failed_reconciliation = Reconciler::RECONCILIATION_OK;

        if ($assertion[0] === '!') {
            $assertion = substr($assertion, 1);
            $is_negation = true;
        }

        if ($assertion[0] === '=') {
            $assertion = substr($assertion, 1);
            $is_strict_equality = true;
            $is_equality = true;
        }

        if ($assertion[0] === '~') {
            $assertion = substr($assertion, 1);
            $is_loose_equality = true;
            $is_equality = true;
        }

        $original_assertion = $assertion;

        if ($assertion[0] === '@') {
            $assertion = 'falsy';
            $is_negation = true;
        }

        if ($existing_var_type === null
            && is_string($key)
            && VariableFetchAnalyzer::isSuperGlobal($key)
        ) {
            $existing_var_type = VariableFetchAnalyzer::getGlobalType($key, $codebase->analysis_php_version_id);
        }

        if ($existing_var_type === null) {
            return self::getMissingType(
                $assertion,
                $is_negation,
                $inside_loop,
                $is_equality,
                $template_type_map
            );
        }

        $old_var_type_string = $existing_var_type->getId();

        if ($is_negation) {
            return NegatedAssertionReconciler::reconcile(
                $statements_analyzer,
                $assertion,
                $is_strict_equality,
                $is_loose_equality,
                $existing_var_type,
                $template_type_map,
                $old_var_type_string,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $inside_loop
            );
        }

        $simply_reconciled_type = SimpleAssertionReconciler::reconcile(
            $assertion,
            $codebase,
            $existing_var_type,
            $key,
            $negated,
            $code_location,
            $suppressed_issues,
            $failed_reconciliation,
            $is_equality,
            $is_strict_equality,
            $inside_loop
        );

        if ($simply_reconciled_type) {
            return $simply_reconciled_type;
        }

        if (strpos($assertion, 'isa-') === 0) {
            $should_return = false;

            $new_type = self::handleIsA(
                $codebase,
                $existing_var_type,
                $assertion,
                $template_type_map,
                $code_location,
                $key,
                $suppressed_issues,
                $should_return
            );

            if ($should_return) {
                return $new_type;
            }
        } elseif (strpos($assertion, 'getclass-') === 0) {
            $assertion = substr($assertion, 9);
            $new_type = Type::parseString($assertion, null, $template_type_map);
        } else {
            $bracket_pos = strpos($assertion, '(');

            if ($bracket_pos) {
                return self::handleLiteralEquality(
                    $statements_analyzer,
                    $assertion,
                    $bracket_pos,
                    $is_loose_equality,
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if ($assertion === 'loaded-class-string') {
                $assertion = 'class-string';
            }

            try {
                $new_type = Type::parseString($assertion, null, $template_type_map);
            } catch (TypeParseTreeException $e) {
                $new_type = Type::getMixed();
            }
        }

        if ($existing_var_type->hasMixed()) {
            if ($is_loose_equality
                && $new_type->hasScalarType()
            ) {
                return $existing_var_type;
            }

            return $new_type;
        }

        $refined_type = self::refine(
            $statements_analyzer,
            $assertion,
            $original_assertion,
            $new_type,
            $existing_var_type,
            $template_type_map,
            $key,
            $negated,
            $code_location,
            $is_equality,
            $is_loose_equality,
            $suppressed_issues,
            $failed_reconciliation
        );

        return TypeExpander::expandUnion(
            $codebase,
            $refined_type,
            null,
            null,
            null,
            true,
            false,
            false,
            true
        );
    }

    /**
     * @param array<string, array<string, Union>> $template_type_map
     */
    private static function getMissingType(
        string $assertion,
        bool $is_negation,
        bool $inside_loop,
        bool $is_equality,
        array $template_type_map
    ): Union {
        if (($assertion === 'isset' && !$is_negation)
            || ($assertion === 'empty' && $is_negation)
        ) {
            return Type::getMixed($inside_loop);
        }

        if ($assertion === 'array-key-exists'
            || $assertion === 'non-empty-countable'
            || strpos($assertion, 'has-at-least-') === 0
            || strpos($assertion, 'has-exactly-') === 0
        ) {
            return Type::getMixed();
        }

        if (!$is_negation && $assertion !== 'falsy' && $assertion !== 'empty') {
            if ($is_equality) {
                $bracket_pos = strpos($assertion, '(');

                if ($bracket_pos) {
                    $assertion = substr($assertion, 0, $bracket_pos);
                }
            }

            try {
                return Type::parseString($assertion, null, $template_type_map);
            } catch (Exception $e) {
                return Type::getMixed();
            }
        }

        return Type::getMixed();
    }

    /**
     * This method is called when SimpleAssertionReconciler was not enough. It receives the existing type, the assertion
     * and also a new type created from the assertion string.
     *
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     * @param   string[]    $suppressed_issues
     * @param   array<string, array<string, Union>> $template_type_map
     * @param-out Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function refine(
        StatementsAnalyzer $statements_analyzer,
        string $assertion,
        string $original_assertion,
        Union $new_type,
        Union $existing_var_type,
        array $template_type_map,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        bool $is_equality,
        bool $is_loose_equality,
        array $suppressed_issues,
        int &$failed_reconciliation
    ): Union {
        $codebase = $statements_analyzer->getCodebase();

        $old_var_type_string = $existing_var_type->getId();

        $new_type_has_interface = false;

        if ($new_type->hasObjectType()) {
            foreach ($new_type->getAtomicTypes() as $new_type_part) {
                if ($new_type_part instanceof TNamedObject &&
                    $codebase->interfaceExists($new_type_part->value)
                ) {
                    $new_type_has_interface = true;
                    break;
                }
            }
        }

        $old_type_has_interface = false;

        if ($existing_var_type->hasObjectType()) {
            foreach ($existing_var_type->getAtomicTypes() as $existing_type_part) {
                if ($existing_type_part instanceof TNamedObject &&
                    $codebase->interfaceExists($existing_type_part->value)
                ) {
                    $old_type_has_interface = true;
                    break;
                }
            }
        }

        try {
            if (strpos($assertion, '<') || strpos($assertion, '[') || strpos($assertion, '{')) {
                $new_type_union = Type::parseString($assertion);

                $new_type_part = $new_type_union->getSingleAtomic();
            } else {
                $new_type_part = Atomic::create($assertion, null, $template_type_map);
            }
        } catch (TypeParseTreeException $e) {
            $new_type_part = new TMixed();

            if ($code_location) {
                IssueBuffer::maybeAdd(
                    new InvalidDocblock(
                        $assertion . ' cannot be used in an assertion',
                        $code_location
                    ),
                    $suppressed_issues
                );
            }
        }

        if ($new_type_part instanceof TTemplateParam
            && $new_type_part->as->isSingle()
        ) {
            $new_as_atomic = $new_type_part->as->getSingleAtomic();

            $acceptable_atomic_types = [];

            foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                if ($existing_var_type_part instanceof TNamedObject
                    || $existing_var_type_part instanceof TTemplateParam
                ) {
                    $new_type_part->addIntersectionType($existing_var_type_part);
                    $acceptable_atomic_types[] = clone $existing_var_type_part;
                } else {
                    if (AtomicTypeComparator::isContainedBy(
                        $codebase,
                        $existing_var_type_part,
                        $new_as_atomic
                    )) {
                        $acceptable_atomic_types[] = clone $existing_var_type_part;
                    }
                }
            }

            if ($acceptable_atomic_types) {
                $new_type_part->as = new Union($acceptable_atomic_types);

                return new Union([$new_type_part]);
            }
        }

        if ($new_type_part instanceof TKeyedArray) {
            $acceptable_atomic_types = [];

            foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                if ($existing_var_type_part instanceof TKeyedArray) {
                    if (!array_intersect_key(
                        $existing_var_type_part->properties,
                        $new_type_part->properties
                    )) {
                        $existing_var_type_part = clone $existing_var_type_part;
                        $existing_var_type_part->properties = array_merge(
                            $existing_var_type_part->properties,
                            $new_type_part->properties
                        );

                        $acceptable_atomic_types[] = $existing_var_type_part;
                    }
                }
            }

            if ($acceptable_atomic_types) {
                return new Union($acceptable_atomic_types);
            }
        }

        if ($new_type_part instanceof TNamedObject
            && ($new_type_has_interface || $old_type_has_interface)
            && !UnionTypeComparator::canExpressionTypesBeIdentical(
                $codebase,
                $new_type,
                $existing_var_type,
                false
            )
        ) {
            $acceptable_atomic_types = [];

            foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                if (AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $existing_var_type_part,
                    $new_type_part
                )) {
                    $acceptable_atomic_types[] = clone $existing_var_type_part;
                    continue;
                }

                if ($existing_var_type_part instanceof TNamedObject
                    && ($codebase->classExists($existing_var_type_part->value)
                        || $codebase->interfaceExists($existing_var_type_part->value))
                ) {
                    $existing_var_type_part = clone $existing_var_type_part;
                    $existing_var_type_part->addIntersectionType($new_type_part);
                    $acceptable_atomic_types[] = $existing_var_type_part;
                }

                if ($existing_var_type_part instanceof TTemplateParam) {
                    $existing_var_type_part = clone $existing_var_type_part;
                    $existing_var_type_part->addIntersectionType($new_type_part);
                    $acceptable_atomic_types[] = $existing_var_type_part;
                }
            }

            if ($acceptable_atomic_types) {
                return new Union($acceptable_atomic_types);
            }
        } elseif (!$new_type->hasMixed()) {
            $has_match = true;

            if ($key
                && $code_location
                && $new_type->getId() === $existing_var_type->getId()
                //even if two objects are the same, equality is not guaranteed
                // example: (ErrorException and TypeError are both Throwable but not equal)
                && !$new_type->hasNamedObjectType()
                && !$is_equality
                && !($original_assertion === 'loaded-class-string' && $old_var_type_string === 'class-string')
                && (!($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
                    || ($key !== '$this'
                        && !($existing_var_type->hasLiteralClassString() && $new_type->hasLiteralClassString())))
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $original_assertion,
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            $any_scalar_type_match_found = false;

            if ($code_location
                && $key
                && !$is_equality
                && $new_type_part instanceof TNamedObject
                && !$new_type_has_interface
                && (!($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
                    || ($key !== '$this'
                        && !($existing_var_type->hasLiteralClassString() && $new_type->hasLiteralClassString())))
                && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $existing_var_type,
                    $new_type,
                    false,
                    false,
                    null,
                    false,
                    false
                )
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $original_assertion,
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            $new_type = self::filterTypeWithAnother(
                $codebase,
                $existing_var_type,
                $new_type,
                $template_type_map,
                $has_match,
                $any_scalar_type_match_found
            );

            if ($code_location
                && !$has_match
                && (!$is_loose_equality || !$any_scalar_type_match_found)
            ) {
                if ($assertion === 'null') {
                    if ($existing_var_type->from_docblock) {
                        IssueBuffer::maybeAdd(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for ' . $key . ' - docblock-defined type '
                                    . $existing_var_type . ' does not contain null',
                                $code_location,
                                $existing_var_type->getId() . ' null'
                            ),
                            $suppressed_issues
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new TypeDoesNotContainNull(
                                'Cannot resolve types for ' . $key . ' - ' . $existing_var_type
                                    . ' does not contain null',
                                $code_location,
                                $existing_var_type->getId()
                            ),
                            $suppressed_issues
                        );
                    }
                } elseif (!($statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer)
                    || ($key !== '$this'
                        && !($existing_var_type->hasLiteralClassString() && $new_type->hasLiteralClassString()))
                ) {
                    if ($existing_var_type->from_docblock) {
                        IssueBuffer::maybeAdd(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for ' . $key . ' - docblock-defined type '
                                    . $existing_var_type->getId() . ' does not contain ' . $new_type->getId(),
                                $code_location,
                                $existing_var_type->getId() . ' ' . $new_type->getId()
                            ),
                            $suppressed_issues
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new TypeDoesNotContainType(
                                'Cannot resolve types for ' . $key . ' - ' . $existing_var_type->getId()
                                    . ' does not contain ' . $new_type->getId(),
                                $code_location,
                                $existing_var_type->getId() . ' ' . $new_type->getId()
                            ),
                            $suppressed_issues
                        );
                    }
                }

                $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;
            }
        }

        return $new_type;
    }

    /**
     * This method receives two types. The goal is to use datas in the new type to reduce the existing_type to a more
     * precise version. For example: new is `array<int>` old is `list<mixed>` so the result is `list<int>`
     *
     * @param array<string, array<string, Union>> $template_type_map
     *
     * @psalm-suppress ComplexMethod we'd probably want to extract specific handling blocks at the end and also allow
     * early return once a specific case has been handled
     */
    private static function filterTypeWithAnother(
        Codebase $codebase,
        Union $existing_type,
        Union $new_type,
        array $template_type_map,
        bool &$has_match = false,
        bool &$any_scalar_type_match_found = false
    ): Union {
        $matching_atomic_types = [];

        $has_cloned_type = false;

        foreach ($new_type->getAtomicTypes() as $new_type_part) {
            $has_local_match = false;

            foreach ($existing_type->getAtomicTypes() as $key => $existing_type_part) {
                // special workaround because PHP allows floats to contain ints, but we don’t want this
                // behaviour here
                if ($existing_type_part instanceof TFloat
                    && $new_type_part instanceof TInt
                ) {
                    $any_scalar_type_match_found = true;
                    continue;
                }

                $atomic_comparison_results = new TypeComparisonResult();

                if ($existing_type_part instanceof TNamedObject) {
                    $existing_type_part->was_static = false;
                }

                $atomic_contained_by = AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $new_type_part,
                    $existing_type_part,
                    true,
                    false,
                    $atomic_comparison_results
                );

                if ($atomic_contained_by) {
                    $has_local_match = true;

                    if ($atomic_comparison_results->type_coerced
                        && get_class($new_type_part) === TNamedObject::class
                        && $existing_type_part instanceof TGenericObject
                    ) {
                        // this is a hack - it's not actually rigorous, as the params may be different
                        $matching_atomic_types[] = new TGenericObject(
                            $new_type_part->value,
                            $existing_type_part->type_params
                        );
                    } elseif ($new_type_part instanceof TNamedObject
                        && $existing_type_part instanceof TTemplateParam
                        && $existing_type_part->as->hasObjectType()
                    ) {
                        $existing_type_part = clone $existing_type_part;
                        $existing_type_part->as = self::filterTypeWithAnother(
                            $codebase,
                            $existing_type_part->as,
                            new Union([$new_type_part]),
                            $template_type_map
                        );

                        $matching_atomic_types[] = $existing_type_part;
                    } else {
                        $matching_atomic_types[] = clone $new_type_part;
                    }

                    continue;
                }

                if (AtomicTypeComparator::isContainedBy(
                    $codebase,
                    $existing_type_part,
                    $new_type_part,
                    false,
                    false,
                    null
                )) {
                    $has_local_match = true;
                    $matching_atomic_types[] = $existing_type_part;

                    continue;
                }

                if ($existing_type_part instanceof TNamedObject
                    && $new_type_part instanceof TNamedObject
                    && ($codebase->interfaceExists($existing_type_part->value)
                        || $codebase->interfaceExists($new_type_part->value))
                ) {
                    $matching_atomic_type = clone $new_type_part;
                    $matching_atomic_type->extra_types[$existing_type_part->getKey()] = $existing_type_part;
                    $matching_atomic_types[] = $matching_atomic_type;
                    $has_local_match = true;

                    continue;
                }

                if ($new_type_part instanceof TKeyedArray
                    && $existing_type_part instanceof TList
                ) {
                    $new_type_key = $new_type_part->getGenericKeyType();
                    $new_type_value = $new_type_part->getGenericValueType();

                    if (!$new_type_key->hasString()) {
                        $has_param_match = false;

                        $new_type_value = self::filterTypeWithAnother(
                            $codebase,
                            $existing_type_part->type_param,
                            $new_type_value,
                            $template_type_map,
                            $has_param_match,
                            $any_scalar_type_match_found
                        );

                        $hybrid_type_part = new TKeyedArray($new_type_part->properties);
                        $hybrid_type_part->previous_key_type = Type::getInt();
                        $hybrid_type_part->previous_value_type = $new_type_value;
                        $hybrid_type_part->is_list = true;

                        if (!$has_cloned_type) {
                            $new_type = clone $new_type;
                            $has_cloned_type = true;
                        }

                        $has_local_match = true;

                        $new_type->removeType($key);
                        $new_type->addType($hybrid_type_part);

                        continue;
                    }
                }

                if ($new_type_part instanceof TTemplateParam
                    && $existing_type_part instanceof TTemplateParam
                    && $new_type_part->param_name !== $existing_type_part->param_name
                    && $new_type_part->as->hasObject()
                    && $existing_type_part->as->hasObject()
                ) {
                    $matching_atomic_type = clone $new_type_part;

                    $matching_atomic_type->extra_types[$existing_type_part->getKey()] = $existing_type_part;
                    $matching_atomic_types[] = $matching_atomic_type;
                    $has_local_match = true;

                    continue;
                }

                //we filter both types of standard iterables
                if (($new_type_part instanceof TGenericObject
                        || $new_type_part instanceof TArray
                        || $new_type_part instanceof TIterable)
                    && ($existing_type_part instanceof TGenericObject
                        || $existing_type_part instanceof TArray
                        || $existing_type_part instanceof TIterable)
                    && count($new_type_part->type_params) === count($existing_type_part->type_params)
                ) {
                    $has_any_param_match = false;

                    foreach ($new_type_part->type_params as $i => $new_param) {
                        $existing_param = $existing_type_part->type_params[$i];

                        $has_param_match = true;

                        $new_param_id = $new_param->getId();

                        $new_param = self::filterTypeWithAnother(
                            $codebase,
                            $existing_param,
                            $new_param,
                            $template_type_map,
                            $has_param_match,
                            $any_scalar_type_match_found
                        );

                        if ($template_type_map) {
                            TemplateInferredTypeReplacer::replace(
                                $new_param,
                                new TemplateResult([], $template_type_map),
                                $codebase
                            );
                        }

                        $existing_type->bustCache();

                        if ($has_param_match
                            && $existing_type_part->type_params[$i]->getId() !== $new_param_id
                        ) {
                            /** @psalm-suppress PropertyTypeCoercion */
                            $existing_type_part->type_params[$i] = $new_param;

                            if (!$has_local_match) {
                                $has_any_param_match = true;
                            }
                        }
                    }

                    if ($has_any_param_match) {
                        $has_local_match = true;
                        $matching_atomic_types[] = $existing_type_part;
                        $atomic_comparison_results->type_coerced = true;
                    }
                }

                //we filter the second part of a list with the second part of standard iterables
                if (($new_type_part instanceof TArray
                        || $new_type_part instanceof TIterable)
                    && $existing_type_part instanceof TList
                ) {
                    $has_any_param_match = false;

                    $new_param = $new_type_part->type_params[1];
                    $existing_param = $existing_type_part->type_param;

                    $has_param_match = true;

                    $new_param = self::filterTypeWithAnother(
                        $codebase,
                        $existing_param,
                        $new_param,
                        $template_type_map,
                        $has_param_match,
                        $any_scalar_type_match_found
                    );

                    if ($template_type_map) {
                        TemplateInferredTypeReplacer::replace(
                            $new_param,
                            new TemplateResult([], $template_type_map),
                            $codebase
                        );
                    }

                    $existing_type->bustCache();

                    if ($has_param_match
                        && $existing_type_part->type_param->getId() !== $new_param->getId()
                    ) {
                        $existing_type_part->type_param = $new_param;

                        if (!$has_local_match) {
                            $has_any_param_match = true;
                        }
                    }

                    if ($has_any_param_match) {
                        $has_local_match = true;
                        $matching_atomic_types[] = $existing_type_part;
                        $atomic_comparison_results->type_coerced = true;
                    }
                }

                //we filter each property of a Keyed Array with the second part of standard iterables
                if (($new_type_part instanceof TArray
                        || $new_type_part instanceof TIterable)
                    && $existing_type_part instanceof TKeyedArray
                ) {
                    $has_any_param_match = false;

                    $new_param = $new_type_part->type_params[1];
                    foreach ($existing_type_part->properties as $property_key => $existing_param) {
                        $has_param_match = true;

                        $new_param = self::filterTypeWithAnother(
                            $codebase,
                            $existing_param,
                            $new_param,
                            $template_type_map,
                            $has_param_match,
                            $any_scalar_type_match_found
                        );

                        if ($template_type_map) {
                            TemplateInferredTypeReplacer::replace(
                                $new_param,
                                new TemplateResult([], $template_type_map),
                                $codebase
                            );
                        }

                        if ($has_param_match
                            && $existing_type_part->properties[$property_key]->getId() !== $new_param->getId()
                        ) {
                            $existing_type_part->properties[$property_key] = $new_param;

                            if (!$has_local_match) {
                                $has_any_param_match = true;
                            }
                        }
                    }

                    $existing_type->bustCache();

                    if ($has_any_param_match) {
                        $has_local_match = true;
                        $matching_atomic_types[] = $existing_type_part;
                        $atomic_comparison_results->type_coerced = true;
                    }
                }

                //These partial match wouldn't have been handled by AtomicTypeComparator
                $new_range = null;
                if ($new_type_part instanceof TIntRange && $existing_type_part instanceof TPositiveInt) {
                    $new_range = TIntRange::intersectIntRanges(
                        TIntRange::convertToIntRange($existing_type_part),
                        $new_type_part
                    );
                } elseif ($existing_type_part instanceof TIntRange
                    && $new_type_part instanceof TPositiveInt
                ) {
                    $new_range = TIntRange::intersectIntRanges(
                        $existing_type_part,
                        TIntRange::convertToIntRange($new_type_part)
                    );
                } elseif ($new_type_part instanceof TIntRange
                    && $existing_type_part instanceof TIntRange
                ) {
                    $new_range = TIntRange::intersectIntRanges(
                        $existing_type_part,
                        $new_type_part
                    );
                }

                if ($new_range !== null) {
                    $has_local_match = true;
                    $matching_atomic_types[] = $new_range;
                }

                if ($atomic_comparison_results->type_coerced) {
                    continue;
                }

                if ($atomic_comparison_results->scalar_type_match_found) {
                    $any_scalar_type_match_found = true;
                }
            }

            if (!$has_local_match) {
                $has_match = false;
                break;
            }
        }

        if ($matching_atomic_types) {
            return new Union($matching_atomic_types);
        }

        return $new_type;
    }

    /**
     * @param  string[]          $suppressed_issues
     */
    private static function handleLiteralEquality(
        StatementsAnalyzer $statements_analyzer,
        string             $assertion,
        int                $bracket_pos,
        bool               $is_loose_equality,
        Union              $existing_var_type,
        string             $old_var_type_string,
        ?string            $var_id,
        bool               $negated,
        ?CodeLocation      $code_location,
        array              $suppressed_issues
    ): Union {
        $value = substr($assertion, $bracket_pos + 1, -1);

        $scalar_type = substr($assertion, 0, $bracket_pos);

        $existing_var_atomic_types = [];

        foreach ($existing_var_type->getAtomicTypes() as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TClassConstant) {
                $expanded = TypeExpander::expandAtomic(
                    $statements_analyzer->getCodebase(),
                    $existing_var_atomic_type,
                    $existing_var_atomic_type->fq_classlike_name,
                    $existing_var_atomic_type->fq_classlike_name,
                    null,
                    true,
                    true
                );

                if ($expanded instanceof Atomic) {
                    $existing_var_atomic_types[$expanded->getKey()] = $expanded;
                } else {
                    foreach ($expanded as $atomic_type) {
                        $existing_var_atomic_types[$atomic_type->getKey()] = $atomic_type;
                    }
                }
            } else {
                $existing_var_atomic_types[$existing_var_atomic_type->getKey()] = $existing_var_atomic_type;
            }
        }

        if ($scalar_type === 'int') {
            return self::handleLiteralEqualityWithInt(
                $statements_analyzer,
                $assertion,
                $bracket_pos,
                $is_loose_equality,
                $existing_var_type,
                $existing_var_atomic_types,
                $old_var_type_string,
                $var_id,
                $negated,
                $code_location,
                $suppressed_issues
            );
        } elseif ($scalar_type === 'string'
            || $scalar_type === 'class-string'
            || $scalar_type === 'interface-string'
            || $scalar_type === 'callable-string'
            || $scalar_type === 'trait-string'
        ) {
            return self::handleLiteralEqualityWithString(
                $statements_analyzer,
                $assertion,
                $scalar_type,
                $bracket_pos,
                $is_loose_equality,
                $existing_var_type,
                $existing_var_atomic_types,
                $old_var_type_string,
                $var_id,
                $negated,
                $code_location,
                $suppressed_issues
            );
        } elseif ($scalar_type === 'float') {
            return self::handleLiteralEqualityWithFloat(
                $statements_analyzer,
                $assertion,
                $bracket_pos,
                $is_loose_equality,
                $existing_var_type,
                $existing_var_atomic_types,
                $old_var_type_string,
                $var_id,
                $negated,
                $code_location,
                $suppressed_issues
            );
        } elseif ($scalar_type === 'enum') {
            [$fq_enum_name, $case_name] = explode('::', $value);

            if ($existing_var_type->hasMixed()) {
                if ($is_loose_equality) {
                    return $existing_var_type;
                }

                return new Union([new TEnumCase($fq_enum_name, $case_name)]);
            }

            $can_be_equal = false;
            $did_remove_type = false;

            foreach ($existing_var_atomic_types as $atomic_key => $atomic_type) {
                if (get_class($atomic_type) === TNamedObject::class
                    && $atomic_type->value === $fq_enum_name
                ) {
                    $can_be_equal = true;
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_key);
                    $existing_var_type->addType(new TEnumCase($fq_enum_name, $case_name));
                } elseif ($atomic_key !== $assertion) {
                    $existing_var_type->removeType($atomic_key);
                    $did_remove_type = true;
                } else {
                    $can_be_equal = true;
                }
            }

            if ($var_id
                && $code_location
                && (!$can_be_equal || (!$did_remove_type && count($existing_var_atomic_types) === 1))
            ) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $var_id,
                    $assertion,
                    $can_be_equal,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        return $existing_var_type;
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     * @param string[]     $suppressed_issues
     */
    private static function handleLiteralEqualityWithInt(
        StatementsAnalyzer $statements_analyzer,
        string             $assertion,
        int                $bracket_pos,
        bool               $is_loose_equality,
        Union              $existing_var_type,
        array              $existing_var_atomic_types,
        string             $old_var_type_string,
        ?string            $var_id,
        bool               $negated,
        ?CodeLocation      $code_location,
        array              $suppressed_issues
    ): Union {
        $value = (int) substr($assertion, $bracket_pos + 1, -1);

        // we create the literal that is being asserted. We'll return this when we're sure this is the resulting type
        $literal_asserted_type = new Union([new TLiteralInt($value)]);
        $literal_asserted_type->from_docblock = $existing_var_type->from_docblock;

        $compatible_int_type = self::getCompatibleIntType(
            $existing_var_type,
            $existing_var_atomic_types,
            $value,
            $is_loose_equality
        );

        if ($compatible_int_type !== null) {
            return $compatible_int_type;
        }

        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TPositiveInt && $value > 0) {
                return $literal_asserted_type;
            }

            if ($existing_var_atomic_type instanceof TIntRange && $existing_var_atomic_type->contains($value)) {
                return $literal_asserted_type;
            }

            if ($existing_var_atomic_type instanceof TLiteralInt && $existing_var_atomic_type->value === $value) {
                //if we're here, we check that we had at least another type in the union, otherwise it's redundant

                if ($existing_var_type->isSingleIntLiteral()) {
                    if ($var_id && $code_location) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $assertion,
                            true,
                            $negated,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                    return $existing_var_type;
                }
                return $literal_asserted_type;
            }

            if ($existing_var_atomic_type instanceof TInt && !$existing_var_atomic_type instanceof TLiteralInt) {
                return $literal_asserted_type;
            }

            if ($existing_var_atomic_type instanceof TTemplateParam) {
                $compatible_int_type = self::getCompatibleIntType(
                    $existing_var_type,
                    $existing_var_atomic_type->as->getAtomicTypes(),
                    $value,
                    $is_loose_equality
                );
                if ($compatible_int_type !== null) {
                    return $compatible_int_type;
                }

                $existing_var_atomic_type = clone $existing_var_atomic_type;

                $existing_var_atomic_type->as = self::handleLiteralEquality(
                    $statements_analyzer,
                    $assertion,
                    $bracket_pos,
                    false,
                    $existing_var_atomic_type->as,
                    $old_var_type_string,
                    $var_id,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );

                return new Union([$existing_var_atomic_type]);
            }

            if ($is_loose_equality
                && $existing_var_atomic_type instanceof TLiteralFloat
                && (int)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }

            if ($is_loose_equality
                && $existing_var_atomic_type instanceof TLiteralString
                && (int)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }
        }

        //here we'll accept non-literal type that *could* match on loose equality and return the original type
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            //here we'll accept non-literal type that *could* match on loose equality and return the original type
            if ($is_loose_equality) {
                if ($existing_var_atomic_type instanceof TString
                    && !$existing_var_atomic_type instanceof TLiteralString
                ) {
                    return $existing_var_type;
                }

                if ($existing_var_atomic_type instanceof TFloat
                    && !$existing_var_atomic_type instanceof TLiteralFloat
                ) {
                    return $existing_var_type;
                }
            }
        }

        //if we're here, no type was eligible for the given literal. We'll emit an impossible error for this assertion
        if ($var_id && $code_location) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $old_var_type_string,
                $var_id,
                $assertion,
                false,
                $negated,
                $code_location,
                $suppressed_issues
            );
        }

        return Type::getNever();
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     * @param string[]     $suppressed_issues
     */
    private static function handleLiteralEqualityWithString(
        StatementsAnalyzer $statements_analyzer,
        string             $assertion,
        string             $scalar_type,
        int                $bracket_pos,
        bool               $is_loose_equality,
        Union              $existing_var_type,
        array              $existing_var_atomic_types,
        string             $old_var_type_string,
        ?string            $var_id,
        bool               $negated,
        ?CodeLocation      $code_location,
        array              $suppressed_issues
    ): Union {
        $value = substr($assertion, $bracket_pos + 1, -1);

        // we create the literal that is being asserted. We'll return this when we're sure this is the resulting type
        $literal_asserted_type_string = new Union([new TLiteralString($value)]);
        $literal_asserted_type_string->from_docblock = $existing_var_type->from_docblock;
        $literal_asserted_type_classstring = new Union([new TLiteralClassString($value)]);
        $literal_asserted_type_classstring->from_docblock = $existing_var_type->from_docblock;

        $compatible_string_type = self::getCompatibleStringType(
            $existing_var_type,
            $existing_var_atomic_types,
            $value,
            $scalar_type,
            $is_loose_equality
        );

        if ($compatible_string_type !== null) {
            return $compatible_string_type;
        }

        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TLiteralString && $existing_var_atomic_type->value === $value) {
                //if we're here, we check that we had at least another type in the union, otherwise it's redundant

                if ($existing_var_type->isSingleStringLiteral()) {
                    if ($var_id && $code_location) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $assertion,
                            true,
                            $negated,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                    return $existing_var_type;
                }

                if ($scalar_type === 'class-string'
                    || $scalar_type === 'interface-string'
                    || $scalar_type === 'trait-string'
                ) {
                    return $literal_asserted_type_classstring;
                }

                return $literal_asserted_type_string;
            }

            if ($existing_var_atomic_type instanceof TString && !$existing_var_atomic_type instanceof TLiteralString) {
                if ($scalar_type === 'class-string'
                    || $scalar_type === 'interface-string'
                    || $scalar_type === 'trait-string'
                ) {
                    return $literal_asserted_type_classstring;
                }

                return $literal_asserted_type_string;
            }

            if ($existing_var_atomic_type instanceof TTemplateParam) {
                $compatible_string_type = self::getCompatibleStringType(
                    $existing_var_type,
                    $existing_var_atomic_type->as->getAtomicTypes(),
                    $value,
                    $scalar_type,
                    $is_loose_equality
                );
                if ($compatible_string_type !== null) {
                    return $compatible_string_type;
                }

                if ($existing_var_atomic_type->as->hasString()) {
                    if ($scalar_type === 'class-string'
                        || $scalar_type === 'interface-string'
                        || $scalar_type === 'trait-string'
                    ) {
                        return $literal_asserted_type_classstring;
                    }

                    return $literal_asserted_type_string;
                }

                $existing_var_atomic_type = clone $existing_var_atomic_type;

                $existing_var_atomic_type->as = self::handleLiteralEquality(
                    $statements_analyzer,
                    $assertion,
                    $bracket_pos,
                    false,
                    $existing_var_atomic_type->as,
                    $old_var_type_string,
                    $var_id,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );

                return new Union([$existing_var_atomic_type]);
            }

            if ($is_loose_equality
                && $existing_var_atomic_type instanceof TLiteralInt
                && (string)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }

            if ($is_loose_equality
                && $existing_var_atomic_type instanceof TLiteralFloat
                && (string)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }
        }

        //here we'll accept non-literal type that *could* match on loose equality and return the original type
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            //here we'll accept non-literal type that *could* match on loose equality and return the original type
            if ($is_loose_equality) {
                if ($existing_var_atomic_type instanceof TInt
                    && !$existing_var_atomic_type instanceof TLiteralInt
                ) {
                    return $existing_var_type;
                }

                if ($existing_var_atomic_type instanceof TFloat
                    && !$existing_var_atomic_type instanceof TLiteralFloat
                ) {
                    return $existing_var_type;
                }
            }
        }

        //if we're here, no type was eligible for the given literal. We'll emit an impossible error for this assertion
        if ($var_id && $code_location) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $old_var_type_string,
                $var_id,
                $assertion,
                false,
                $negated,
                $code_location,
                $suppressed_issues
            );
        }

        return Type::getNever();
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     * @param string[]     $suppressed_issues
     */
    private static function handleLiteralEqualityWithFloat(
        StatementsAnalyzer $statements_analyzer,
        string             $assertion,
        int                $bracket_pos,
        bool               $is_loose_equality,
        Union              $existing_var_type,
        array              $existing_var_atomic_types,
        string             $old_var_type_string,
        ?string            $var_id,
        bool               $negated,
        ?CodeLocation      $code_location,
        array              $suppressed_issues
    ): Union {
        $value = (float)substr($assertion, $bracket_pos + 1, -1);

        // we create the literal that is being asserted. We'll return this when we're sure this is the resulting type
        $literal_asserted_type = new Union([new TLiteralFloat($value)]);
        $literal_asserted_type->from_docblock = $existing_var_type->from_docblock;

        $compatible_float_type = self::getCompatibleFloatType(
            $existing_var_type,
            $existing_var_atomic_types,
            $value,
            $is_loose_equality
        );

        if ($compatible_float_type !== null) {
            return $compatible_float_type;
        }

        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TLiteralFloat && $existing_var_atomic_type->value === $value) {
                //if we're here, we check that we had at least another type in the union, otherwise it's redundant

                if ($existing_var_type->isSingleFloatLiteral()) {
                    if ($var_id && $code_location) {
                        self::triggerIssueForImpossible(
                            $existing_var_type,
                            $old_var_type_string,
                            $var_id,
                            $assertion,
                            true,
                            $negated,
                            $code_location,
                            $suppressed_issues
                        );
                    }
                    return $existing_var_type;
                }

                return $literal_asserted_type;
            }

            if ($existing_var_atomic_type instanceof TFloat && !$existing_var_atomic_type instanceof TLiteralFloat) {
                return $literal_asserted_type;
            }

            if ($existing_var_atomic_type instanceof TTemplateParam) {
                $compatible_float_type = self::getCompatibleFloatType(
                    $existing_var_type,
                    $existing_var_atomic_type->as->getAtomicTypes(),
                    $value,
                    $is_loose_equality
                );
                if ($compatible_float_type !== null) {
                    return $compatible_float_type;
                }

                if ($existing_var_atomic_type->as->hasFloat()) {
                    return $literal_asserted_type;
                }

                $existing_var_atomic_type = clone $existing_var_atomic_type;

                $existing_var_atomic_type->as = self::handleLiteralEquality(
                    $statements_analyzer,
                    $assertion,
                    $bracket_pos,
                    false,
                    $existing_var_atomic_type->as,
                    $old_var_type_string,
                    $var_id,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );

                return new Union([$existing_var_atomic_type]);
            }

            if ($is_loose_equality
                && $existing_var_atomic_type instanceof TLiteralInt
                && (float)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }

            if ($is_loose_equality
                && $existing_var_atomic_type instanceof TLiteralString
                && (float)$existing_var_atomic_type->value === $value
            ) {
                return new Union([$existing_var_atomic_type]);
            }
        }

        //here we'll accept non-literal type that *could* match on loose equality and return the original type
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($is_loose_equality) {
                if ($existing_var_atomic_type instanceof TInt
                    && !$existing_var_atomic_type instanceof TLiteralInt
                ) {
                    return $existing_var_type;
                }

                if ($existing_var_atomic_type instanceof TString
                    && !$existing_var_atomic_type instanceof TLiteralString
                ) {
                    return $existing_var_type;
                }
            }
        }

        //if we're here, no type was eligible for the given literal. We'll emit an impossible error for this assertion
        if ($var_id && $code_location) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $old_var_type_string,
                $var_id,
                $assertion,
                false,
                $negated,
                $code_location,
                $suppressed_issues
            );
        }

        return Type::getNever();
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     */
    private static function getCompatibleIntType(
        Union $existing_var_type,
        array $existing_var_atomic_types,
        int $value,
        bool $is_loose_equality
    ): ?Union {
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TMixed
                || $existing_var_atomic_type instanceof TScalar
                || $existing_var_atomic_type instanceof TNumeric
                || $existing_var_atomic_type instanceof TArrayKey
            ) {
                if ($is_loose_equality) {
                    return $existing_var_type;
                }

                $asserted_type = new Union([new TLiteralInt($value)]);
                $asserted_type->from_docblock = $existing_var_type->from_docblock;
                return $asserted_type;
            }
        }

        return null;
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     */
    private static function getCompatibleStringType(
        Union $existing_var_type,
        array $existing_var_atomic_types,
        string $value,
        string $scalar_type,
        bool $is_loose_equality
    ): ?Union {
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TMixed
                || $existing_var_atomic_type instanceof TScalar
                || $existing_var_atomic_type instanceof TArrayKey
            ) {
                if ($is_loose_equality) {
                    return $existing_var_type;
                }

                if ($scalar_type === 'class-string'
                    || $scalar_type === 'interface-string'
                    || $scalar_type === 'trait-string'
                ) {
                    $asserted_type = new Union([new TLiteralClassString($value)]);
                    $asserted_type->from_docblock = $existing_var_type->from_docblock;
                    return $asserted_type;
                }

                $asserted_type = new Union([new TLiteralString($value)]);
                $asserted_type->from_docblock = $existing_var_type->from_docblock;
                return $asserted_type;
            }
        }

        return null;
    }

    /**
     * @param array<string, Atomic> $existing_var_atomic_types
     */
    private static function getCompatibleFloatType(
        Union $existing_var_type,
        array $existing_var_atomic_types,
        float $value,
        bool $is_loose_equality
    ): ?Union {
        foreach ($existing_var_atomic_types as $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TMixed
                || $existing_var_atomic_type instanceof TScalar
                || $existing_var_atomic_type instanceof TNumeric
            ) {
                if ($is_loose_equality) {
                    return $existing_var_type;
                }

                $asserted_type = new Union([new TLiteralFloat($value)]);
                $asserted_type->from_docblock = $existing_var_type->from_docblock;
                return $asserted_type;
            }
        }

        return null;
    }

    /**
     * @param array<string, array<string, Union>> $template_type_map
     * @param array<string>           $suppressed_issues
     */
    private static function handleIsA(
        Codebase $codebase,
        Union $existing_var_type,
        string &$assertion,
        array $template_type_map,
        ?CodeLocation $code_location,
        ?string $key,
        array $suppressed_issues,
        bool &$should_return
    ): Union {
        $assertion = substr($assertion, 4);

        $allow_string_comparison = false;

        if (strpos($assertion, 'string-') === 0) {
            $assertion = substr($assertion, 7);
            $allow_string_comparison = true;
        }

        if ($existing_var_type->hasMixed()) {
            $type = new Union([
                new TNamedObject($assertion),
            ]);

            if ($allow_string_comparison) {
                $type->addType(
                    new TClassString(
                        $assertion,
                        new TNamedObject($assertion)
                    )
                );
            }

            $should_return = true;
            return $type;
        }

        $existing_has_object = $existing_var_type->hasObjectType();
        $existing_has_string = $existing_var_type->hasString();

        if ($existing_has_object && !$existing_has_string) {
            return Type::parseString($assertion, null, $template_type_map);
        }

        if ($existing_has_string && !$existing_has_object) {
            if (!$allow_string_comparison && $code_location) {
                IssueBuffer::maybeAdd(
                    new TypeDoesNotContainType(
                        'Cannot allow string comparison to object for ' . $key,
                        $code_location,
                        null
                    ),
                    $suppressed_issues
                );

                return Type::getMixed();
            } else {
                $new_type_has_interface_string = $codebase->interfaceExists($assertion);

                $old_type_has_interface_string = false;

                foreach ($existing_var_type->getAtomicTypes() as $existing_type_part) {
                    if ($existing_type_part instanceof TClassString
                        && $existing_type_part->as_type
                        && $codebase->interfaceExists($existing_type_part->as_type->value)
                    ) {
                        $old_type_has_interface_string = true;
                        break;
                    }
                }

                if (isset($template_type_map[$assertion])) {
                    $new_type = Type::parseString(
                        'class-string<' . $assertion . '>',
                        null,
                        $template_type_map
                    );
                } else {
                    $new_type = Type::getClassString($assertion);
                }

                if ((
                        $new_type_has_interface_string
                        && !UnionTypeComparator::isContainedBy(
                            $codebase,
                            $existing_var_type,
                            $new_type
                        )
                    )
                    || (
                        $old_type_has_interface_string
                        && !UnionTypeComparator::isContainedBy(
                            $codebase,
                            $new_type,
                            $existing_var_type
                        )
                    )
                ) {
                    $new_type_part = Atomic::create($assertion, null, $template_type_map);

                    $acceptable_atomic_types = [];

                    foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_part) {
                        if (!$new_type_part instanceof TNamedObject
                            || !$existing_var_type_part instanceof TClassString
                        ) {
                            $acceptable_atomic_types = [];

                            break;
                        }

                        if (!$existing_var_type_part->as_type instanceof TNamedObject) {
                            $acceptable_atomic_types = [];

                            break;
                        }

                        $existing_var_type_part = $existing_var_type_part->as_type;

                        if (AtomicTypeComparator::isContainedBy(
                            $codebase,
                            $existing_var_type_part,
                            $new_type_part
                        )) {
                            $acceptable_atomic_types[] = clone $existing_var_type_part;
                            continue;
                        }

                        if ($codebase->classExists($existing_var_type_part->value)
                            || $codebase->interfaceExists($existing_var_type_part->value)
                        ) {
                            $existing_var_type_part = clone $existing_var_type_part;
                            $existing_var_type_part->addIntersectionType($new_type_part);
                            $acceptable_atomic_types[] = $existing_var_type_part;
                        }
                    }

                    if (count($acceptable_atomic_types) === 1) {
                        $should_return = true;

                        return new Union([
                            new TClassString('object', $acceptable_atomic_types[0]),
                        ]);
                    }
                }
            }

            return $new_type;
        } else {
            return Type::getMixed();
        }
    }
}
