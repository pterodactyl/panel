<?php

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Issue\DocblockTypeContradiction;
use Psalm\Issue\RedundantPropertyInitializationCheck;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableArray;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyScalar;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function assert;
use function get_class;
use function max;
use function strpos;
use function substr;

class SimpleNegatedAssertionReconciler extends Reconciler
{
    /**
     * @param  string[]   $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    public static function reconcile(
        Codebase $codebase,
        string $assertion,
        Union $existing_var_type,
        ?string $key = null,
        bool $negated = false,
        ?CodeLocation $code_location = null,
        array $suppressed_issues = [],
        int &$failed_reconciliation = Reconciler::RECONCILIATION_EMPTY,
        bool $is_equality = false,
        bool $inside_loop = false
    ): ?Union {
        $old_var_type_string = $existing_var_type->getId();

        if ($assertion === 'isset') {
            if ($existing_var_type->possibly_undefined) {
                return Type::getEmpty();
            }

            if (!$existing_var_type->isNullable()
                && $key
                && strpos($key, '[') === false
                && (!$existing_var_type->hasMixed() || $existing_var_type->isAlwaysTruthy())
            ) {
                if ($code_location) {
                    if ($existing_var_type->from_static_property) {
                        IssueBuffer::maybeAdd(
                            new RedundantPropertyInitializationCheck(
                                'Static property ' . $key . ' with type '
                                    . $existing_var_type
                                    . ' has unexpected isset check — should it be nullable?',
                                $code_location
                            ),
                            $suppressed_issues
                        );
                    } elseif ($existing_var_type->from_property) {
                        IssueBuffer::maybeAdd(
                            new RedundantPropertyInitializationCheck(
                                'Property ' . $key . ' with type '
                                    . $existing_var_type . ' should already be set in the constructor',
                                $code_location
                            ),
                            $suppressed_issues
                        );
                    } elseif ($existing_var_type->from_docblock) {
                        IssueBuffer::maybeAdd(
                            new DocblockTypeContradiction(
                                'Cannot resolve types for ' . $key . ' with docblock-defined type '
                                    . $existing_var_type . ' and !isset assertion',
                                $code_location,
                                null
                            ),
                            $suppressed_issues
                        );
                    } else {
                        IssueBuffer::maybeAdd(
                            new TypeDoesNotContainType(
                                'Cannot resolve types for ' . $key . ' with type '
                                    . $existing_var_type . ' and !isset assertion',
                                $code_location,
                                null
                            ),
                            $suppressed_issues
                        );
                    }
                }

                return $existing_var_type->from_docblock
                    ? Type::getNull()
                    : Type::getEmpty();
            }

            return Type::getNull();
        }

        if ($assertion === 'array-key-exists') {
            return Type::getEmpty();
        }

        if (strpos($assertion, 'in-array-') === 0) {
            $assertion = substr($assertion, 9);
            $new_var_type = Type::parseString($assertion);

            $intersection = Type::intersectUnionTypes(
                $new_var_type,
                $existing_var_type,
                $codebase
            );

            if ($intersection === null) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $existing_var_type->getId(),
                        $key,
                        '!' . $assertion,
                        true,
                        $negated,
                        $code_location,
                        $suppressed_issues
                    );
                }

                $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;
            }

            return $existing_var_type;
        }

        if ($assertion === 'object' && !$existing_var_type->hasMixed()) {
            return self::reconcileObject(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'scalar' && !$existing_var_type->hasMixed()) {
            return self::reconcileScalar(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'resource' && !$existing_var_type->hasMixed()) {
            return self::reconcileResource(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'bool' && !$existing_var_type->hasMixed()) {
            return self::reconcileBool(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'numeric' && !$existing_var_type->hasMixed()) {
            return self::reconcileNumeric(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'float' && !$existing_var_type->hasMixed()) {
            return self::reconcileFloat(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'int' && !$existing_var_type->hasMixed()) {
            return self::reconcileInt(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'string' && !$existing_var_type->hasMixed()) {
            return self::reconcileString(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'array' && !$existing_var_type->hasMixed()) {
            return self::reconcileArray(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'falsy' || $assertion === 'empty') {
            return self::reconcileFalsyOrEmpty(
                $assertion,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                false
            );
        }

        if ($assertion === 'null' && !$existing_var_type->hasMixed()) {
            return self::reconcileNull(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'false' && !$existing_var_type->hasMixed()) {
            return self::reconcileFalse(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'non-empty-countable') {
            return self::reconcileNonEmptyCountable(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                null
            );
        }

        if ($assertion === 'callable') {
            return self::reconcileCallable(
                $existing_var_type
            );
        }

        if (strpos($assertion, 'has-at-least-') === 0) {
            return self::reconcileNonEmptyCountable(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                (int) substr($assertion, 13)
            );
        }

        if (strpos($assertion, 'has-exactly-') === 0) {
            return $existing_var_type;
        }

        if ($assertion[0] === '>') {
            return self::reconcileSuperiorTo(
                $existing_var_type,
                $assertion,
                $inside_loop,
                $old_var_type_string,
                $key,
                $negated,
                $code_location,
                $suppressed_issues
            );
        }

        if ($assertion[0] === '<') {
            return self::reconcileInferiorTo(
                $existing_var_type,
                $assertion,
                $inside_loop,
                $old_var_type_string,
                $key,
                $negated,
                $code_location,
                $suppressed_issues
            );
        }

        return null;
    }

    private static function reconcileCallable(
        Union $existing_var_type
    ): Union {
        foreach ($existing_var_type->getAtomicTypes() as $atomic_key => $type) {
            if ($type instanceof TLiteralString
                && InternalCallMapHandler::inCallMap($type->value)
            ) {
                $existing_var_type->removeType($atomic_key);
            }

            if ($type->isCallableType()) {
                $existing_var_type->removeType($atomic_key);
            }
        }

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileBool(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_bool_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$type->as->hasBool()) {
                    $non_bool_types[] = $type;
                }

                $did_remove_type = true;
            } elseif (!$type instanceof TBool
                || ($is_equality && get_class($type) === TBool::class)
            ) {
                if ($type instanceof TScalar) {
                    $did_remove_type = true;
                    $non_bool_types[] = new TString();
                    $non_bool_types[] = new TInt();
                    $non_bool_types[] = new TFloat();
                } else {
                    $non_bool_types[] = $type;
                }
            } else {
                $did_remove_type = true;
            }
        }

        if (!$did_remove_type || !$non_bool_types) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!bool',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_bool_types) {
            return new Union($non_bool_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileNonEmptyCountable(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        ?int $min_count
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if (isset($existing_var_atomic_types['array'])) {
            $array_atomic_type = $existing_var_atomic_types['array'];
            $did_remove_type = false;

            if (($array_atomic_type instanceof TNonEmptyArray
                    || $array_atomic_type instanceof TNonEmptyList)
                && ($min_count === null
                    || $array_atomic_type->count >= $min_count)
            ) {
                $did_remove_type = true;

                $existing_var_type->removeType('array');
            } elseif ($array_atomic_type->getId() !== 'array<empty, empty>') {
                $did_remove_type = true;

                if (!$min_count) {
                    $existing_var_type->addType(new TArray(
                        [
                            new Union([new TEmpty]),
                            new Union([new TEmpty]),
                        ]
                    ));
                }
            } elseif ($array_atomic_type instanceof TKeyedArray) {
                $did_remove_type = true;

                foreach ($array_atomic_type->properties as $property_type) {
                    if (!$property_type->possibly_undefined) {
                        $did_remove_type = false;
                        break;
                    }
                }
            }

            if (!$is_equality
                && !$existing_var_type->hasMixed()
                && (!$did_remove_type || $existing_var_type->isUnionEmpty())
            ) {
                if ($key && $code_location) {
                    self::triggerIssueForImpossible(
                        $existing_var_type,
                        $old_var_type_string,
                        $key,
                        '!non-empty-countable',
                        !$did_remove_type,
                        $negated,
                        $code_location,
                        $suppressed_issues
                    );
                }
            }
        }

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileNull(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $did_remove_type = false;

        if ($existing_var_type->hasType('null')) {
            $did_remove_type = true;
            $existing_var_type->removeType('null');
        }

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type->as = self::reconcileNull(
                    $type->as,
                    null,
                    false,
                    null,
                    $suppressed_issues,
                    $failed_reconciliation,
                    $is_equality
                );

                $did_remove_type = true;
                $existing_var_type->bustCache();
            }
        }

        if (!$did_remove_type || $existing_var_type->isUnionEmpty()) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!null',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if (!$existing_var_type->isUnionEmpty()) {
            return $existing_var_type;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileFalse(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $did_remove_type = $existing_var_type->hasScalar();

        if ($existing_var_type->hasType('false')) {
            $did_remove_type = true;
            $existing_var_type->removeType('false');
        }

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type->as = self::reconcileFalse(
                    $type->as,
                    null,
                    false,
                    null,
                    $suppressed_issues,
                    $failed_reconciliation,
                    $is_equality
                );

                $did_remove_type = true;
                $existing_var_type->bustCache();
            }
        }

        if (!$did_remove_type || $existing_var_type->isUnionEmpty()) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!false',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if (!$existing_var_type->isUnionEmpty()) {
            return $existing_var_type;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileFalsyOrEmpty(
        string $assertion,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $recursive_check
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        //empty is used a lot to check for array offset existence, so we have to silent errors a lot
        $is_empty_assertion = $assertion === 'empty';

        $did_remove_type = $existing_var_type->possibly_undefined
            || $existing_var_type->possibly_undefined_from_try;

        foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_key => $existing_var_type_part) {
            //if any atomic in the union is either always falsy, we remove it. If not always truthy, we mark the check
            //as not redundant.
            if ($existing_var_type_part->isFalsy()) {
                $did_remove_type = true;
                $existing_var_type->removeType($existing_var_type_key);
            } elseif ($existing_var_type->possibly_undefined
                || $existing_var_type->possibly_undefined_from_try
                || !$existing_var_type_part->isTruthy()
            ) {
                $did_remove_type = true;
            }
        }

        if ($did_remove_type && $existing_var_type->isUnionEmpty()) {
            //every type was removed, this is an impossible assertion
            if ($code_location && $key && !$is_empty_assertion && !$recursive_check) {
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

            $failed_reconciliation = 2;

            return Type::getEmpty();
        }

        if (!$did_remove_type) {
            if ($code_location && $key && !$is_empty_assertion && !$recursive_check) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!' . $assertion,
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            $failed_reconciliation = 1;

            return $existing_var_type;
        }

        $existing_var_type->possibly_undefined = false;
        $existing_var_type->possibly_undefined_from_try = false;

        if ($existing_var_type->hasType('bool')) {
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TTrue());
        }

        if ($existing_var_type->hasArray()) {
            $array_atomic_type = $existing_var_type->getAtomicTypes()['array'];

            if ($array_atomic_type instanceof TArray
                && !$array_atomic_type instanceof TNonEmptyArray
            ) {
                $existing_var_type->removeType('array');
                $existing_var_type->addType(
                    new TNonEmptyArray(
                        $array_atomic_type->type_params
                    )
                );
            } elseif ($array_atomic_type instanceof TList
                && !$array_atomic_type instanceof TNonEmptyList
            ) {
                $existing_var_type->removeType('array');
                $existing_var_type->addType(
                    new TNonEmptyList(
                        $array_atomic_type->type_param
                    )
                );
            }
        }

        if ($existing_var_type->hasMixed()) {
            $mixed_atomic_type = $existing_var_type->getAtomicTypes()['mixed'];

            if (get_class($mixed_atomic_type) === TMixed::class) {
                $existing_var_type->removeType('mixed');
                $existing_var_type->addType(new TNonEmptyMixed());
            }
        }

        if ($existing_var_type->hasScalar()) {
            $scalar_atomic_type = $existing_var_type->getAtomicTypes()['scalar'];

            if (get_class($scalar_atomic_type) === TScalar::class) {
                $existing_var_type->removeType('scalar');
                $existing_var_type->addType(new TNonEmptyScalar());
            }
        }

        if ($existing_var_type->hasType('string')) {
            $string_atomic_type = $existing_var_type->getAtomicTypes()['string'];

            if (get_class($string_atomic_type) === TString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TNonFalsyString());
            } elseif (get_class($string_atomic_type) === TLowercaseString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TNonEmptyLowercaseString());
            } elseif (get_class($string_atomic_type) === TNonspecificLiteralString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TNonEmptyNonspecificLiteralString());
            } elseif (get_class($string_atomic_type) === TNonEmptyString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TNonFalsyString());
            }
        }

        if ($existing_var_type->hasInt()) {
            $existing_range_types = $existing_var_type->getRangeInts();

            if ($existing_range_types) {
                foreach ($existing_range_types as $int_key => $literal_type) {
                    if ($literal_type->contains(0)) {
                        $existing_var_type->removeType($int_key);
                        if ($literal_type->min_bound === null || $literal_type->min_bound <= -1) {
                            $existing_var_type->addType(new TIntRange($literal_type->min_bound, -1));
                        }
                        if ($literal_type->max_bound === null || $literal_type->max_bound >= 1) {
                            $existing_var_type->addType(new TIntRange(1, $literal_type->max_bound));
                        }
                    }
                }
            }

            if ($existing_var_type->isSingle()) {
                return $existing_var_type;
            }
        }

        foreach ($existing_var_type->getAtomicTypes() as $type_key => $existing_var_atomic_type) {
            if ($existing_var_atomic_type instanceof TTemplateParam) {
                if (!$existing_var_atomic_type->as->isMixed()) {
                    $template_did_fail = 0;

                    $existing_var_atomic_type = clone $existing_var_atomic_type;

                    $existing_var_atomic_type->as = self::reconcileFalsyOrEmpty(
                        $assertion,
                        $existing_var_atomic_type->as,
                        $key,
                        $negated,
                        $code_location,
                        $suppressed_issues,
                        $template_did_fail,
                        true
                    );

                    if (!$template_did_fail) {
                        $existing_var_type->removeType($type_key);
                        $existing_var_type->addType($existing_var_atomic_type);
                    }
                }
            }
        }

        assert(!$existing_var_type->isUnionEmpty());
        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileScalar(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_scalar_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileScalar(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_scalar_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_scalar_types[] = $type;
                }
            } elseif (!($type instanceof Scalar)) {
                $non_scalar_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_scalar_types[] = $type;
                }
            }
        }

        if (!$did_remove_type || !$non_scalar_types) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!scalar',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_scalar_types) {
            $type = new Union($non_scalar_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileObject(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_object_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileObject(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_object_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_object_types[] = $type;
                }
            } elseif ($type instanceof TCallable) {
                $non_object_types[] = new TCallableArray([
                    Type::getArrayKey(),
                    Type::getMixed()
                ]);
                $non_object_types[] = new TCallableString();
                $did_remove_type = true;
            } elseif ($type instanceof TIterable) {
                $clone_type = clone $type;

                self::refineArrayKey($clone_type->type_params[0]);

                $non_object_types[] = new TArray($clone_type->type_params);

                $did_remove_type = true;
            } elseif (!$type->isObjectType()) {
                $non_object_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_object_types[] = $type;
                }
            }
        }

        if (!$non_object_types || !$did_remove_type) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!object',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_object_types) {
            $type = new Union($non_object_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileNumeric(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_numeric_types = [];
        $did_remove_type = $existing_var_type->hasString()
            || $existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileNumeric(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_numeric_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_numeric_types[] = $type;
                }
            } elseif ($type instanceof TArrayKey) {
                $did_remove_type = true;
                $non_numeric_types[] = new TString();
            } elseif (!$type->isNumericType()) {
                $non_numeric_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_numeric_types[] = $type;
                }
            }
        }

        if (!$non_numeric_types || !$did_remove_type) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!numeric',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_numeric_types) {
            $type = new Union($non_numeric_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileInt(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_int_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileInt(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_int_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_int_types[] = $type;
                }
            } elseif ($type instanceof TArrayKey) {
                $did_remove_type = true;
                $non_int_types[] = new TString();
            } elseif ($type instanceof TScalar) {
                $did_remove_type = true;
                $non_int_types[] = new TString();
                $non_int_types[] = new TFloat();
                $non_int_types[] = new TBool();
            } elseif ($type instanceof TInt) {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_int_types[] = $type;
                } elseif ($existing_var_type->from_calculation) {
                    $non_int_types[] = new TFloat();
                }
            } elseif ($type instanceof TNumeric) {
                $did_remove_type = true;
                $non_int_types[] = new TString();
                $non_int_types[] = new TFloat();
            } else {
                $non_int_types[] = $type;
            }
        }

        if (!$non_int_types || !$did_remove_type) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!int',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_int_types) {
            $type = new Union($non_int_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileFloat(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_float_types = [];
        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileFloat(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_float_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_float_types[] = $type;
                }
            } elseif ($type instanceof TScalar) {
                $did_remove_type = true;
                $non_float_types[] = new TString();
                $non_float_types[] = new TInt();
                $non_float_types[] = new TBool();
            } elseif ($type instanceof TFloat) {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_float_types[] = $type;
                }
            } elseif ($type instanceof TNumeric) {
                $did_remove_type = true;
                $non_float_types[] = new TString();
                $non_float_types[] = new TInt();
            } else {
                $non_float_types[] = $type;
            }
        }

        if (!$non_float_types || !$did_remove_type) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!float',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_float_types) {
            $type = new Union($non_float_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileString(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_string_types = [];
        $did_remove_type = $existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileString(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_string_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_string_types[] = $type;
                }
            } elseif ($type instanceof TArrayKey) {
                $non_string_types[] = new TInt();
                $did_remove_type = true;
            } elseif ($type instanceof TCallable) {
                $non_string_types[] = new TCallableArray([
                    Type::getArrayKey(),
                    Type::getMixed()
                ]);
                $non_string_types[] = new TCallableObject();
                $did_remove_type = true;
            } elseif ($type instanceof TNumeric) {
                $non_string_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TScalar) {
                $did_remove_type = true;
                $non_string_types[] = new TFloat();
                $non_string_types[] = new TInt();
                $non_string_types[] = new TBool();
            } elseif (!$type instanceof TString) {
                $non_string_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_string_types[] = $type;
                }
            }
        }

        if (!$non_string_types || !$did_remove_type) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!string',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_string_types) {
            $type = new Union($non_string_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileArray(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $non_array_types = [];
        $did_remove_type = $existing_var_type->hasScalar();

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                if (!$is_equality && !$type->as->isMixed()) {
                    $template_did_fail = 0;

                    $type = clone $type;

                    $type->as = self::reconcileArray(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $template_did_fail,
                        $is_equality
                    );

                    $did_remove_type = true;

                    if (!$template_did_fail) {
                        $non_array_types[] = $type;
                    }
                } else {
                    $did_remove_type = true;
                    $non_array_types[] = $type;
                }
            } elseif ($type instanceof TCallable) {
                $non_array_types[] = new TCallableString();
                $non_array_types[] = new TCallableObject();
                $did_remove_type = true;
            } elseif ($type instanceof TIterable) {
                if (!$type->type_params[0]->isMixed() || !$type->type_params[1]->isMixed()) {
                    $non_array_types[] = new TGenericObject('Traversable', $type->type_params);
                } else {
                    $non_array_types[] = new TNamedObject('Traversable');
                }

                $did_remove_type = true;
            } elseif (!$type instanceof TArray
                && !$type instanceof TKeyedArray
                && !$type instanceof TList
            ) {
                $non_array_types[] = $type;
            } else {
                $did_remove_type = true;

                if ($is_equality) {
                    $non_array_types[] = $type;
                }
            }
        }

        if ((!$non_array_types || !$did_remove_type)) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!array',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if ($non_array_types) {
            $type = new Union($non_array_types);
            $type->ignore_falsable_issues = $existing_var_type->ignore_falsable_issues;
            $type->ignore_nullable_issues = $existing_var_type->ignore_nullable_issues;
            $type->from_docblock = $existing_var_type->from_docblock;
            return $type;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileResource(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $did_remove_type = false;

        if ($existing_var_type->hasType('resource')) {
            $did_remove_type = true;
            $existing_var_type->removeType('resource');
        }

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TTemplateParam) {
                $type->as = self::reconcileResource(
                    $type->as,
                    null,
                    false,
                    null,
                    $suppressed_issues,
                    $failed_reconciliation,
                    $is_equality
                );

                $did_remove_type = true;
                $existing_var_type->bustCache();
            }
        }

        if (!$did_remove_type || $existing_var_type->isUnionEmpty()) {
            if ($key && $code_location && !$is_equality) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    '!resource',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            if (!$did_remove_type) {
                $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
            }
        }

        if (!$existing_var_type->isUnionEmpty()) {
            return $existing_var_type;
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param string[] $suppressed_issues
     */
    private static function reconcileSuperiorTo(
        Union         $existing_var_type,
        string        $assertion,
        bool          $inside_loop,
        string        $old_var_type_string,
        ?string       $var_id,
        bool          $negated,
        ?CodeLocation $code_location,
        array         $suppressed_issues
    ): Union {
        $assertion_value = (int)substr($assertion, 1) - 1;

        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($inside_loop) {
                continue;
            }

            if ($atomic_type instanceof TIntRange) {
                if ($atomic_type->contains($assertion_value)) {
                    // if the range contains the assertion, the range must be adapted
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_type->getKey());
                    if ($atomic_type->max_bound === null) {
                        $atomic_type->max_bound = $assertion_value;
                    } else {
                        $atomic_type->max_bound = TIntRange::getNewLowestBound(
                            $assertion_value,
                            $atomic_type->max_bound
                        );
                    }
                    $existing_var_type->addType($atomic_type);
                } elseif ($atomic_type->isLesserThan($assertion_value)) {
                    // if the range is lesser than the assertion, the check is redundant
                } elseif ($atomic_type->isGreaterThan($assertion_value)) {
                    // if the range is greater than the assertion, the type must be removed
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_type->getKey());
                }
            } elseif ($atomic_type instanceof TLiteralInt) {
                if ($atomic_type->value > $assertion_value) {
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_type->getKey());
                } /*elseif ($inside_loop) {
                    //when inside a loop, allow the range to extends the type
                    $existing_var_type->removeType($atomic_type->getKey());
                    if ($atomic_type->value < $assertion_value) {
                        $existing_var_type->addType(new TIntRange($atomic_type->value, $assertion_value));
                    } else {
                        $existing_var_type->addType(new TIntRange($assertion_value, $atomic_type->value));
                    }
                }*/
            } elseif ($atomic_type instanceof TPositiveInt) {
                $did_remove_type = true;
                $existing_var_type->removeType($atomic_type->getKey());
                if ($assertion_value >= 1) {
                    $existing_var_type->addType(new TIntRange(1, $assertion_value));
                }
            } elseif ($atomic_type instanceof TInt) {
                $did_remove_type = true;
                $existing_var_type->removeType($atomic_type->getKey());
                $existing_var_type->addType(new TIntRange(null, $assertion_value));
            } else {
                // we assume that other types may have been removed (empty strings? numeric strings?)
                //It may be worth refining to improve reconciliation while keeping in mind we're on loose comparison
                $did_remove_type = true;
            }
        }

        if (!$inside_loop && !$did_remove_type && $var_id && $code_location) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $old_var_type_string,
                $var_id,
                '!'.$assertion,
                true,
                $negated,
                $code_location,
                $suppressed_issues
            );
        }

        if ($existing_var_type->isUnionEmpty()) {
            if ($var_id && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $var_id,
                    '!'.$assertion,
                    false,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
            $existing_var_type->addType(new TNever());
        }

        return $existing_var_type;
    }

    /**
     * @param string[] $suppressed_issues
     */
    private static function reconcileInferiorTo(
        Union         $existing_var_type,
        string        $assertion,
        bool          $inside_loop,
        string        $old_var_type_string,
        ?string       $var_id,
        bool          $negated,
        ?CodeLocation $code_location,
        array         $suppressed_issues
    ): Union {
        $assertion_value = (int)substr($assertion, 1) + 1;

        $did_remove_type = false;

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($inside_loop) {
                continue;
            }

            if ($atomic_type instanceof TIntRange) {
                if ($atomic_type->contains($assertion_value)) {
                    // if the range contains the assertion, the range must be adapted
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_type->getKey());
                    if ($atomic_type->min_bound === null) {
                        $atomic_type->min_bound = $assertion_value;
                    } else {
                        $atomic_type->min_bound = max($atomic_type->min_bound, $assertion_value);
                    }
                    $existing_var_type->addType($atomic_type);
                } elseif ($atomic_type->isLesserThan($assertion_value)) {
                    // if the range is lesser than the assertion, the type must be removed
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_type->getKey());
                } elseif ($atomic_type->isGreaterThan($assertion_value)) {
                    // if the range is greater than the assertion, the check is redundant
                }
            } elseif ($atomic_type instanceof TLiteralInt) {
                if ($atomic_type->value < $assertion_value) {
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_type->getKey());
                } /* elseif ($inside_loop) {
                    //when inside a loop, allow the range to extends the type
                    $existing_var_type->removeType($atomic_type->getKey());
                    if ($atomic_type->value < $assertion_value) {
                        $existing_var_type->addType(new TIntRange($atomic_type->value, $assertion_value));
                    } else {
                        $existing_var_type->addType(new TIntRange($assertion_value, $atomic_type->value));
                    }
                }*/
            } elseif ($atomic_type instanceof TPositiveInt) {
                if ($assertion_value > 1) {
                    $did_remove_type = true;
                    $existing_var_type->removeType($atomic_type->getKey());
                    $existing_var_type->addType(new TIntRange($assertion_value, null));
                }
            } elseif ($atomic_type instanceof TInt) {
                $did_remove_type = true;
                $existing_var_type->removeType($atomic_type->getKey());
                $existing_var_type->addType(new TIntRange($assertion_value, null));
            } else {
                // we assume that other types may have been removed (empty strings? numeric strings?)
                //It may be worth refining to improve reconciliation while keeping in mind we're on loose comparison
                $did_remove_type = true;
            }
        }

        if (!$inside_loop && !$did_remove_type && $var_id && $code_location) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $old_var_type_string,
                $var_id,
                '!'.$assertion,
                true,
                $negated,
                $code_location,
                $suppressed_issues
            );
        }

        if ($existing_var_type->isUnionEmpty()) {
            if ($var_id && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $var_id,
                    '!'.$assertion,
                    false,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
            $existing_var_type->addType(new TNever());
        }

        return $existing_var_type;
    }
}
