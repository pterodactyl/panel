<?php

namespace Psalm\Internal\Type;

use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Internal\Codebase\ClassConstantByWildcardResolver;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Type;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableArray;
use Psalm\Type\Atomic\TCallableKeyedArray;
use Psalm\Type\Atomic\TCallableList;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TEmptyNumeric;
use Psalm\Type\Atomic\TEmptyScalar;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function assert;
use function count;
use function explode;
use function get_class;
use function max;
use function min;
use function strpos;
use function substr;

/**
 * This class receives a known type and an assertion (probably coming from AssertionFinder). The goal is to refine
 * the known type using the assertion. For example: old type is `int` assertion is `>5` result is `int<6, max>`.
 * Complex reconciliation takes part in AssertionReconciler if this class couldn't handle the reconciliation
 */
class SimpleAssertionReconciler extends Reconciler
{
    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    public static function reconcile(
        string $assertion,
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key = null,
        bool $negated = false,
        ?CodeLocation $code_location = null,
        array $suppressed_issues = [],
        int &$failed_reconciliation = Reconciler::RECONCILIATION_OK,
        bool $is_equality = false,
        bool $is_strict_equality = false,
        bool $inside_loop = false
    ): ?Union {
        if ($assertion === 'mixed' && $existing_var_type->hasMixed()) {
            return $existing_var_type;
        }

        $old_var_type_string = $existing_var_type->getId();

        if ($assertion === 'isset') {
            return self::reconcileIsset(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                $inside_loop
            );
        }

        if ($assertion === 'array-key-exists') {
            $existing_var_type->possibly_undefined = false;

            return $existing_var_type;
        }

        if (strpos($assertion, 'in-array-') === 0) {
            return self::reconcileInArray(
                $codebase,
                $existing_var_type,
                substr($assertion, 9),
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation
            );
        }

        if (strpos($assertion, 'has-array-key-') === 0) {
            return self::reconcileHasArrayKey(
                $existing_var_type,
                substr($assertion, 14)
            );
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

        if ($assertion === 'object') {
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

        if ($assertion === 'resource') {
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

        if ($assertion === 'callable') {
            return self::reconcileCallable(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'iterable') {
            return self::reconcileIterable(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'array') {
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

        if ($assertion === 'list') {
            return self::reconcileList(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                false
            );
        }

        if ($assertion === 'non-empty-list') {
            return self::reconcileList(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                true
            );
        }

        if ($assertion === 'Traversable') {
            return self::reconcileTraversable(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'countable') {
            return self::reconcileCountable(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'string-array-access') {
            return self::reconcileStringArrayAccess(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $inside_loop
            );
        }

        if ($assertion === 'int-or-string-array-access') {
            return self::reconcileIntArrayAccess(
                $codebase,
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $inside_loop
            );
        }

        if ($assertion === 'numeric') {
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

        if ($assertion === 'scalar') {
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

        if ($assertion === 'bool') {
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

        if ($assertion === 'string') {
            return self::reconcileString(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                $is_strict_equality
            );
        }

        if ($assertion === 'int') {
            return self::reconcileInt(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality,
                $is_strict_equality
            );
        }

        if ($assertion === 'positive-numeric') {
            return self::reconcilePositiveNumeric(
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation,
                $is_equality
            );
        }

        if ($assertion === 'float'
            && $existing_var_type->from_calculation
            && $existing_var_type->hasInt()
        ) {
            return Type::getFloat();
        }

        if ($assertion === 'float' && $is_equality && !$is_strict_equality && $existing_var_type->isString()) {
            return Type::getNumericString();
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
            /** @psalm-suppress ArgumentTypeCoercion */
            return self::reconcileExactlyCountable(
                $existing_var_type,
                (int) substr($assertion, 12)
            );
        }

        if (strpos($assertion, 'hasmethod-') === 0) {
            return self::reconcileHasMethod(
                $codebase,
                substr($assertion, 10),
                $existing_var_type,
                $key,
                $negated,
                $code_location,
                $suppressed_issues,
                $failed_reconciliation
            );
        }

        if (substr($assertion, 0, 15) === 'class-constant(') {
            return self::reconcileClassConstant(
                $codebase,
                substr($assertion, 15, -1),
                $existing_var_type,
                $failed_reconciliation
            );
        }

        if ($existing_var_type->isSingle()
            && $existing_var_type->hasTemplate()
            && strpos($assertion, '-') === false
            && strpos($assertion, '(') === false
        ) {
            foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TTemplateParam) {
                    if ($atomic_type->as->hasMixed()
                        || $atomic_type->as->hasObject()
                    ) {
                        $atomic_type->as = Type::parseString($assertion);

                        return $existing_var_type;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileIsset(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        bool $inside_loop
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        // if key references an array offset
        $did_remove_type = ($key && strpos($key, '['))
            || !$existing_var_type->initialized
            || $existing_var_type->possibly_undefined
            || $existing_var_type->ignore_isset;

        if ($existing_var_type->isNullable()) {
            $existing_var_type->removeType('null');

            $did_remove_type = true;
        }

        if (!$existing_var_type->hasMixed()
            && !$is_equality
            && (!$did_remove_type || $existing_var_type->isUnionEmpty())
            && $key
            && $code_location
        ) {
            self::triggerIssueForImpossible(
                $existing_var_type,
                $old_var_type_string,
                $key,
                'isset',
                !$did_remove_type,
                $negated,
                $code_location,
                $suppressed_issues
            );

            if ($existing_var_type->isUnionEmpty()) {
                $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;
                return Type::getEmpty();
            }
        }

        if ($existing_var_type->hasType('empty')) {
            $existing_var_type->removeType('empty');
            $existing_var_type->addType(new TMixed($inside_loop));
        }

        $existing_var_type->from_property = false;
        $existing_var_type->from_static_property = false;
        $existing_var_type->possibly_undefined = false;
        $existing_var_type->possibly_undefined_from_try = false;
        $existing_var_type->ignore_isset = false;

        return $existing_var_type;
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

        if ($existing_var_type->hasType('array')) {
            $array_atomic_type = $existing_var_type->getAtomicTypes()['array'];
            $did_remove_type = false;

            if ($array_atomic_type instanceof TArray) {
                if (!$array_atomic_type instanceof TNonEmptyArray
                    || ($array_atomic_type->count < $min_count)
                ) {
                    if ($array_atomic_type->getId() === 'array<empty, empty>') {
                        $existing_var_type->removeType('array');
                    } else {
                        $non_empty_array = new TNonEmptyArray(
                            $array_atomic_type->type_params
                        );

                        if ($min_count) {
                            $non_empty_array->count = $min_count;
                        }

                        $existing_var_type->addType($non_empty_array);
                    }

                    $did_remove_type = true;
                }
            } elseif ($array_atomic_type instanceof TList) {
                if (!$array_atomic_type instanceof TNonEmptyList
                    || ($array_atomic_type->count < $min_count)
                ) {
                    $non_empty_list = new TNonEmptyList(
                        $array_atomic_type->type_param
                    );

                    if ($min_count) {
                        $non_empty_list->count = $min_count;
                    }

                    $did_remove_type = true;
                    $existing_var_type->addType($non_empty_list);
                }
            } elseif ($array_atomic_type instanceof TKeyedArray) {
                foreach ($array_atomic_type->properties as $property_type) {
                    if ($property_type->possibly_undefined) {
                        $did_remove_type = true;
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
                        'non-empty-countable',
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
     * @param   positive-int $count
     */
    private static function reconcileExactlyCountable(
        Union $existing_var_type,
        int $count
    ): Union {
        if ($existing_var_type->hasType('array')) {
            $array_atomic_type = $existing_var_type->getAtomicTypes()['array'];

            if ($array_atomic_type instanceof TArray) {
                $non_empty_array = new TNonEmptyArray(
                    $array_atomic_type->type_params
                );

                $non_empty_array->count = $count;

                $existing_var_type->addType(
                    $non_empty_array
                );
            } elseif ($array_atomic_type instanceof TList) {
                $non_empty_list = new TNonEmptyList(
                    $array_atomic_type->type_param
                );

                $non_empty_list->count = $count;

                $existing_var_type->addType(
                    $non_empty_list
                );
            }
        }

        return $existing_var_type;
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcilePositiveNumeric(
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

        $positive_types = [];

        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TLiteralInt) {
                if ($atomic_type->value < 1) {
                    $did_remove_type = true;
                } else {
                    $positive_types[] = $atomic_type;
                }
            } elseif ($atomic_type instanceof TPositiveInt) {
                $positive_types[] = $atomic_type;
            } elseif ($atomic_type instanceof TIntRange) {
                if (!$atomic_type->isPositive()) {
                    $did_remove_type = true;
                }
                $positive_types[] = new TIntRange(
                    $atomic_type->min_bound === null ? 1 : max(1, $atomic_type->min_bound),
                    $atomic_type->max_bound === null ? null : max(1, $atomic_type->max_bound)
                );
            } elseif (get_class($atomic_type) === TInt::class) {
                $positive_types[] = new TPositiveInt();
                $did_remove_type = true;
            } else {
                // for now allow this check everywhere else
                if (!$atomic_type instanceof TNull
                    && !$atomic_type instanceof TFalse
                ) {
                    $positive_types[] = $atomic_type;
                }

                $did_remove_type = true;
            }
        }

        if (!$is_equality
            && !$existing_var_type->hasMixed()
            && (!$did_remove_type || !$positive_types)
        ) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'positive-numeric',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($positive_types) {
            return new Union($positive_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileHasMethod(
        Codebase $codebase,
        string $method_name,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $object_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TNamedObject
                && $codebase->classOrInterfaceExists($type->value)
            ) {
                $object_types[] = $type;

                if (!$codebase->methodExists($type->value . '::' . $method_name)) {
                    $match_found = false;

                    if ($type->extra_types) {
                        foreach ($type->extra_types as $extra_type) {
                            if ($extra_type instanceof TNamedObject
                                && $codebase->classOrInterfaceExists($extra_type->value)
                                && $codebase->methodExists($extra_type->value . '::' . $method_name)
                            ) {
                                $match_found = true;
                            } elseif ($extra_type instanceof TObjectWithProperties) {
                                $match_found = true;

                                if (!isset($extra_type->methods[$method_name])) {
                                    $extra_type->methods[$method_name] = 'object::' . $method_name;
                                    $did_remove_type = true;
                                }
                            }
                        }
                    }

                    if (!$match_found) {
                        $obj = new TObjectWithProperties(
                            [],
                            [$method_name => $type->value . '::' . $method_name]
                        );
                        $type->extra_types[$obj->getKey()] = $obj;
                        $did_remove_type = true;
                    }
                }
            } elseif ($type instanceof TObjectWithProperties) {
                $object_types[] = $type;

                if (!isset($type->methods[$method_name])) {
                    $type->methods[$method_name] = 'object::' . $method_name;
                    $did_remove_type = true;
                }
            } elseif ($type instanceof TObject || $type instanceof TMixed) {
                $object_types[] = new TObjectWithProperties(
                    [],
                    [$method_name =>  'object::' . $method_name]
                );
                $did_remove_type = true;
            } elseif ($type instanceof TString) {
                // we don’t know
                $object_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                $object_types[] = $type;
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if (!$object_types || !$did_remove_type) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'object with method ' . $method_name,
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($object_types) {
            return new Union($object_types);
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
        bool $is_equality,
        bool $is_strict_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed()) {
            if ($is_equality && !$is_strict_equality) {
                return $existing_var_type;
            }

            return Type::getString();
        }

        $string_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TString) {
                $string_types[] = $type;

                if (get_class($type) === TString::class) {
                    $type->from_docblock = false;
                }
            } elseif ($type instanceof TCallable) {
                $string_types[] = new TCallableString;
                $did_remove_type = true;
            } elseif ($type instanceof TNumeric) {
                $string_types[] = new TNumericString;
                $did_remove_type = true;
            } elseif ($type instanceof TScalar || $type instanceof TArrayKey) {
                $string_types[] = new TString;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasString() || $type->as->hasMixed() || $type->as->hasScalar()) {
                    $type = clone $type;

                    $type->as = self::reconcileString(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                        $is_strict_equality
                    );

                    $string_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$string_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'string',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($string_types) {
            return new Union($string_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
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
        bool $is_equality,
        bool $is_strict_equality
    ): Union {
        if ($existing_var_type->hasMixed()) {
            if ($is_equality && !$is_strict_equality) {
                return $existing_var_type;
            }

            return Type::getInt();
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $int_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TInt) {
                $int_types[] = $type;

                if (get_class($type) === TInt::class) {
                    $type->from_docblock = false;
                }

                if ($existing_var_type->from_calculation) {
                    $did_remove_type = true;
                }
            } elseif ($type instanceof TNumeric) {
                $int_types[] = new TInt;
                $did_remove_type = true;
            } elseif ($type instanceof TScalar || $type instanceof TArrayKey) {
                $int_types[] = new TInt;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasInt() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileInt(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality,
                        $is_strict_equality
                    );

                    $int_types[] = $type;
                }

                $did_remove_type = true;
            } elseif ($type instanceof TString && $is_equality && !$is_strict_equality) {
                $int_types[] = new TNumericString();
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$int_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'int',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($int_types) {
            return new Union($int_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
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
        if ($existing_var_type->hasMixed()) {
            return Type::getBool();
        }

        $bool_types = [];
        $did_remove_type = false;

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TBool) {
                $bool_types[] = $type;
                $type->from_docblock = false;
            } elseif ($type instanceof TScalar) {
                $bool_types[] = new TBool;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasBool() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileBool(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $bool_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$bool_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'bool',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($bool_types) {
            return new Union($bool_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
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
        if ($existing_var_type->hasMixed()) {
            return Type::getScalar();
        }

        $scalar_types = [];
        $did_remove_type = false;

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof Scalar) {
                $scalar_types[] = $type;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasScalar() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileScalar(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $scalar_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$scalar_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'scalar',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($scalar_types) {
            return new Union($scalar_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
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
        if ($existing_var_type->hasMixed()) {
            return Type::getNumeric();
        }

        $old_var_type_string = $existing_var_type->getId();

        $numeric_types = [];
        $did_remove_type = false;

        if ($existing_var_type->hasString()) {
            $did_remove_type = true;
            $existing_var_type->removeType('string');
            $existing_var_type->addType(new TNumericString);
        }

        foreach ($existing_var_type->getAtomicTypes() as $type) {
            if ($type instanceof TNumeric || $type instanceof TNumericString) {
                // this is a workaround for a possible issue running
                // is_numeric($a) && is_string($a)
                $did_remove_type = true;
                $numeric_types[] = $type;
            } elseif ($type->isNumericType()) {
                $numeric_types[] = $type;
            } elseif ($type instanceof TScalar) {
                $did_remove_type = true;
                $numeric_types[] = new TNumeric();
            } elseif ($type instanceof TArrayKey) {
                $did_remove_type = true;
                $numeric_types[] = new TInt();
                $numeric_types[] = new TNumericString();
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasNumeric() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileNumeric(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $numeric_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$did_remove_type || !$numeric_types) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'numeric',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($numeric_types) {
            return new Union($numeric_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
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
        if ($existing_var_type->hasMixed()) {
            return Type::getObject();
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $object_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isObjectType()) {
                $object_types[] = $type;
            } elseif ($type instanceof TCallable) {
                $object_types[] = new TCallableObject();
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam
                && $type->as->isMixed()
            ) {
                $type = clone $type;
                $type->as = Type::getObject();
                $object_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasObject() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileObject(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $object_types[] = $type;
                }

                $did_remove_type = true;
            } elseif ($type instanceof TIterable) {
                $clone_type = clone $type;

                self::refineArrayKey($clone_type->type_params[0]);

                $object_types[] = new TGenericObject('Traversable', $clone_type->type_params);

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$object_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'object',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($object_types) {
            return new Union($object_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
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
        if ($existing_var_type->hasMixed()) {
            return Type::getResource();
        }

        $old_var_type_string = $existing_var_type->getId();
        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $resource_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TResource) {
                $resource_types[] = $type;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$resource_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'resource',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($resource_types) {
            return new Union($resource_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileCountable(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();


        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Union([
                new TArray([Type::getArrayKey(), Type::getMixed()]),
                new TNamedObject('Countable'),
            ]);
        }

        $iterable_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isCountable($codebase)) {
                $iterable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $iterable_types[] = new TNamedObject('Countable');
                $did_remove_type = true;
            } elseif ($type instanceof TNamedObject || $type instanceof TIterable) {
                $countable = new TNamedObject('Countable');
                $type->extra_types[$countable->getKey()] = $countable;
                $iterable_types[] = $type;
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$iterable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'countable',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($iterable_types) {
            return new Union($iterable_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileIterable(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Union([new TIterable]);
        }

        $iterable_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isIterable($codebase)) {
                $iterable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $iterable_types[] = new TNamedObject('Traversable');
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$iterable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'iterable',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($iterable_types) {
            return new Union($iterable_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileInArray(
        Codebase $codebase,
        Union $existing_var_type,
        string $assertion,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation
    ): Union {
        $new_var_type = Type::parseString($assertion);

        $intersection = Type::intersectUnionTypes($new_var_type, $existing_var_type, $codebase);

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

            return Type::getMixed();
        }

        return $intersection;
    }

    private static function reconcileHasArrayKey(
        Union $existing_var_type,
        string $assertion
    ): Union {
        foreach ($existing_var_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TKeyedArray) {
                $is_class_string = false;

                if (strpos($assertion, '::class')) {
                    [$assertion] = explode('::', $assertion);
                    $is_class_string = true;
                }

                if (isset($atomic_type->properties[$assertion])) {
                    $atomic_type->properties[$assertion]->possibly_undefined = false;
                } else {
                    $atomic_type->properties[$assertion] = Type::getMixed();

                    if ($is_class_string) {
                        $atomic_type->class_strings[$assertion] = true;
                    }
                }
            }
        }

        return $existing_var_type;
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
        $assertion_value = (int)substr($assertion, 1);

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
                        $atomic_type->min_bound = TIntRange::getNewHighestBound(
                            $assertion_value,
                            $atomic_type->min_bound
                        );
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
                $assertion,
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
                    $assertion,
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
        $assertion_value = (int)substr($assertion, 1);

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
                        $atomic_type->max_bound = min($atomic_type->max_bound, $assertion_value);
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
                $assertion,
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
                    $assertion,
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
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileTraversable(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Union([new TNamedObject('Traversable')]);
        }

        $traversable_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->hasTraversableInterface($codebase)) {
                $traversable_types[] = $type;
            } elseif ($type instanceof TIterable) {
                $clone_type = clone $type;
                $traversable_types[] = new TGenericObject('Traversable', $clone_type->type_params);
                $did_remove_type = true;
            } elseif ($type instanceof TObject) {
                $traversable_types[] = new TNamedObject('Traversable');
                $did_remove_type = true;
            } elseif ($type instanceof TNamedObject) {
                $traversable = new TNamedObject('Traversable');
                $type->extra_types[$traversable->getKey()] = $traversable;
                $traversable_types[] = $type;
                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$traversable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'Traversable',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($traversable_types) {
            return new Union($traversable_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
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

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed()) {
            return Type::getArray();
        }

        $array_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TArray || $type instanceof TKeyedArray || $type instanceof TList) {
                $array_types[] = $type;
            } elseif ($type instanceof TCallable) {
                $array_types[] = new TCallableKeyedArray([
                    new Union([new TClassString, new TObject]),
                    Type::getString()
                ]);

                $did_remove_type = true;
            } elseif ($type instanceof TIterable) {
                $clone_type = clone $type;

                self::refineArrayKey($clone_type->type_params[0]);

                $array_types[] = new TArray($clone_type->type_params);

                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasArray() || $type->as->hasIterable() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileArray(
                        $type->as,
                        null,
                        false,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );

                    $array_types[] = $type;
                }

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$array_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'array',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );

                if (!$did_remove_type) {
                    $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
                }
            }
        }

        if ($array_types) {
            return TypeCombiner::combine($array_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileList(
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality,
        bool $is_non_empty
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return $is_non_empty ? Type::getNonEmptyList() : Type::getList();
        }

        $array_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type instanceof TList
                || ($type instanceof TKeyedArray && $type->is_list)
            ) {
                if ($is_non_empty && $type instanceof TList && !$type instanceof TNonEmptyList) {
                    $array_types[] = new TNonEmptyList($type->type_param);
                    $did_remove_type = true;
                } else {
                    $array_types[] = $type;
                }
            } elseif ($type instanceof TArray || $type instanceof TKeyedArray) {
                if ($type instanceof TKeyedArray) {
                    $type = $type->getGenericArrayType();
                }

                if ($type->type_params[0]->hasArrayKey()
                    || $type->type_params[0]->hasInt()
                ) {
                    if ($type instanceof TNonEmptyArray) {
                        $array_types[] = new TNonEmptyList($type->type_params[1]);
                    } else {
                        $array_types[] = new TList($type->type_params[1]);
                    }
                }

                if ($type->type_params[0]->isEmpty()
                    || $type->type_params[1]->isEmpty()
                ) {
                    //we allow an empty array to pass as a list. We keep the type as empty array though (more precise)
                    $array_types[] = $type;
                }

                $did_remove_type = true;
            } elseif ($type instanceof TCallable) {
                $array_types[] = new TCallableKeyedArray([
                    new Union([new TClassString, new TObject]),
                    Type::getString()
                ]);

                $did_remove_type = true;
            } elseif ($type instanceof TIterable) {
                $clone_type = clone $type;
                $array_types[] = new TList($clone_type->type_params[1]);

                $did_remove_type = true;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$array_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'array',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );

                if (!$did_remove_type) {
                    $failed_reconciliation = Reconciler::RECONCILIATION_REDUNDANT;
                }
            }
        }

        if ($array_types) {
            return TypeCombiner::combine($array_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return $existing_var_type->from_docblock
            ? Type::getMixed()
            : Type::getEmpty();
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileStringArrayAccess(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $inside_loop
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed() || $existing_var_type->hasTemplate()) {
            return new Union([
                new TNonEmptyArray([Type::getArrayKey(), Type::getMixed()]),
                new TNamedObject('ArrayAccess'),
            ]);
        }

        $array_types = [];

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isArrayAccessibleWithStringKey($codebase)) {
                if (get_class($type) === TArray::class) {
                    $array_types[] = new TNonEmptyArray($type->type_params);
                } elseif (get_class($type) === TList::class) {
                    $array_types[] = new TNonEmptyList($type->type_param);
                } else {
                    $array_types[] = $type;
                }
            } elseif ($type instanceof TTemplateParam) {
                $array_types[] = $type;
            }
        }

        if (!$array_types) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'string-array-access',
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($array_types) {
            return new Union($array_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed($inside_loop);
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileIntArrayAccess(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $inside_loop
    ): Union {
        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        if ($existing_var_type->hasMixed()) {
            return Type::getMixed();
        }

        $array_types = [];

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isArrayAccessibleWithIntOrStringKey($codebase)) {
                if (get_class($type) === TArray::class) {
                    $array_types[] = new TNonEmptyArray($type->type_params);
                } else {
                    $array_types[] = $type;
                }
            } elseif ($type instanceof TTemplateParam) {
                $array_types[] = $type;
            }
        }

        if (!$array_types) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'int-or-string-array-access',
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($array_types) {
            return TypeCombiner::combine($array_types);
        }

        $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;

        return Type::getMixed($inside_loop);
    }

    /**
     * @param   string[]  $suppressed_issues
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileCallable(
        Codebase $codebase,
        Union $existing_var_type,
        ?string $key,
        bool $negated,
        ?CodeLocation $code_location,
        array $suppressed_issues,
        int &$failed_reconciliation,
        bool $is_equality
    ): Union {
        if ($existing_var_type->hasMixed()) {
            return Type::parseString('callable');
        }

        $old_var_type_string = $existing_var_type->getId();

        $existing_var_atomic_types = $existing_var_type->getAtomicTypes();

        $callable_types = [];
        $did_remove_type = false;

        foreach ($existing_var_atomic_types as $type) {
            if ($type->isCallableType()) {
                $callable_types[] = $type;
            } elseif ($type instanceof TObject) {
                $callable_types[] = new TCallableObject();
                $did_remove_type = true;
            } elseif ($type instanceof TNamedObject
                && $codebase->classExists($type->value)
                && $codebase->methodExists($type->value . '::__invoke')
            ) {
                $callable_types[] = $type;
            } elseif (get_class($type) === TString::class
                || get_class($type) === TNonEmptyString::class
                || get_class($type) === TNonFalsyString::class
            ) {
                $callable_types[] = new TCallableString();
                $did_remove_type = true;
            } elseif (get_class($type) === TLiteralString::class
                && InternalCallMapHandler::inCallMap($type->value)
            ) {
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TArray) {
                $type = clone $type;
                $type = new TCallableArray($type->type_params);
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TList) {
                $type = clone $type;
                $type = new TCallableList($type->type_param);
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TKeyedArray && count($type->properties) === 2) {
                $type = clone $type;
                $type = new TCallableKeyedArray($type->properties);
                $callable_types[] = $type;
                $did_remove_type = true;
            } elseif ($type instanceof TTemplateParam) {
                if ($type->as->hasCallableType() || $type->as->hasMixed()) {
                    $type = clone $type;

                    $type->as = self::reconcileCallable(
                        $codebase,
                        $type->as,
                        null,
                        $negated,
                        null,
                        $suppressed_issues,
                        $failed_reconciliation,
                        $is_equality
                    );
                }

                $did_remove_type = true;

                $callable_types[] = $type;
            } else {
                $did_remove_type = true;
            }
        }

        if ((!$callable_types || !$did_remove_type) && !$is_equality) {
            if ($key && $code_location) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    'callable',
                    !$did_remove_type,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }
        }

        if ($callable_types) {
            return TypeCombiner::combine($callable_types);
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

        $did_remove_type = $existing_var_type->possibly_undefined
            || $existing_var_type->possibly_undefined_from_try;

        foreach ($existing_var_type->getAtomicTypes() as $existing_var_type_key => $existing_var_type_part) {
            //if any atomic in the union is either always truthy, we remove it. If not always falsy, we mark the check
            //as not redundant.
            if (!$existing_var_type->possibly_undefined
                && !$existing_var_type->possibly_undefined_from_try
                && $existing_var_type_part->isTruthy()
            ) {
                $did_remove_type = true;
                $existing_var_type->removeType($existing_var_type_key);
            } elseif (!$existing_var_type_part->isFalsy()) {
                $did_remove_type = true;
            }
        }

        if ($did_remove_type && $existing_var_type->isUnionEmpty()) {
            //every type was removed, this is an impossible assertion
            if ($code_location && $key && !$recursive_check) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
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
            //nothing was removed, this is a redundant assertion
            if ($code_location && $key && !$recursive_check) {
                self::triggerIssueForImpossible(
                    $existing_var_type,
                    $old_var_type_string,
                    $key,
                    $assertion,
                    true,
                    $negated,
                    $code_location,
                    $suppressed_issues
                );
            }

            $failed_reconciliation = 1;

            return $existing_var_type;
        }

        if ($existing_var_type->hasType('bool')) {
            $existing_var_type->removeType('bool');
            $existing_var_type->addType(new TFalse());
        }

        if ($existing_var_type->hasArray()) {
            $existing_var_type->removeType('array');
            $existing_var_type->addType(new TArray(
                [
                    new Union([new TEmpty()]),
                    new Union([new TEmpty()]),
                ]
            ));
        }

        if ($existing_var_type->hasMixed()) {
            $mixed_atomic_type = $existing_var_type->getAtomicTypes()['mixed'];

            if (get_class($mixed_atomic_type) === TMixed::class) {
                $existing_var_type->removeType('mixed');
                $existing_var_type->addType(new TEmptyMixed());
            }
        }

        if ($existing_var_type->hasScalar()) {
            $scalar_atomic_type = $existing_var_type->getAtomicTypes()['scalar'];

            if (get_class($scalar_atomic_type) === TScalar::class) {
                $existing_var_type->removeType('scalar');
                $existing_var_type->addType(new TEmptyScalar());
            }
        }

        if ($existing_var_type->hasType('string')) {
            $string_atomic_type = $existing_var_type->getAtomicTypes()['string'];

            if (get_class($string_atomic_type) === TString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TLiteralString(''));
                $existing_var_type->addType(new TLiteralString('0'));
            } elseif (get_class($string_atomic_type) === TNonEmptyString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TLiteralString('0'));
            } elseif (get_class($string_atomic_type) === TNonEmptyLowercaseString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TLiteralString('0'));
            } elseif (get_class($string_atomic_type) === TNonEmptyNonspecificLiteralString::class) {
                $existing_var_type->removeType('string');
                $existing_var_type->addType(new TLiteralString('0'));
            }
        }

        if ($existing_var_type->hasInt()) {
            $existing_range_types = $existing_var_type->getRangeInts();

            if ($existing_range_types) {
                foreach ($existing_range_types as $int_key => $literal_type) {
                    if ($literal_type->contains(0)) {
                        $existing_var_type->removeType($int_key);
                        $existing_var_type->addType(new TLiteralInt(0));
                    }
                }
            } else {
                $existing_var_type->removeType('int');
                $existing_var_type->addType(new TLiteralInt(0));
            }
        }

        if ($existing_var_type->hasFloat()) {
            $existing_var_type->removeType('float');
            $existing_var_type->addType(new TLiteralFloat(0.0));
        }

        if ($existing_var_type->hasNumeric()) {
            $existing_var_type->removeType('numeric');
            $existing_var_type->addType(new TEmptyNumeric());
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
                        $recursive_check
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
     * @param Reconciler::RECONCILIATION_* $failed_reconciliation
     */
    private static function reconcileClassConstant(
        Codebase $codebase,
        string $class_constant_expression,
        Union $existing_type,
        int &$failed_reconciliation
    ): Union {
        if (strpos($class_constant_expression, '::') === false) {
            return $existing_type;
        }

        [$class_name, $constant_pattern] = explode('::', $class_constant_expression, 2);

        $resolver = new ClassConstantByWildcardResolver($codebase);
        $matched_class_constant_types = $resolver->resolve($class_name, $constant_pattern);
        if ($matched_class_constant_types === null) {
            return $existing_type;
        }

        if ($matched_class_constant_types === []) {
            $failed_reconciliation = Reconciler::RECONCILIATION_EMPTY;
            return Type::getMixed();
        }

        return TypeCombiner::combine($matched_class_constant_types, $codebase);
    }
}
