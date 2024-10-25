<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TDependentGetClass;
use Psalm\Type\Atomic\TDependentGetDebugType;
use Psalm\Type\Atomic\TDependentGetType;
use Psalm\Type\Atomic\TDependentListKey;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\THtmlEscapedString;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTraitString;
use Psalm\Type\Atomic\TTrue;

use function get_class;
use function is_numeric;
use function strtolower;

/**
 * @internal
 */
class ScalarTypeComparator
{
    public static function isContainedBy(
        Codebase $codebase,
        Scalar $input_type_part,
        Scalar $container_type_part,
        bool $allow_interface_equality = false,
        bool $allow_float_int_equality = true,
        ?TypeComparisonResult $atomic_comparison_result = null
    ): bool {
        if (get_class($container_type_part) === TString::class
            && $input_type_part instanceof TString
        ) {
            return true;
        }

        if (get_class($container_type_part) === TInt::class
            && $input_type_part instanceof TInt
        ) {
            return true;
        }

        if (get_class($container_type_part) === TFloat::class
            && $input_type_part instanceof TFloat
        ) {
            return true;
        }

        if ($container_type_part instanceof TNonEmptyString
            && get_class($input_type_part) === TString::class
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TNonspecificLiteralString
            && ($input_type_part instanceof TLiteralString || $input_type_part instanceof TNonspecificLiteralString)
        ) {
            return true;
        }

        if ($container_type_part instanceof TNonspecificLiteralString) {
            if ($input_type_part instanceof TString) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }
            }

            return false;
        }

        if ($container_type_part instanceof TNonspecificLiteralInt
            && ($input_type_part instanceof TLiteralInt
                || $input_type_part instanceof TNonspecificLiteralInt)
        ) {
            return true;
        }

        if ($container_type_part instanceof TNonspecificLiteralInt) {
            if ($input_type_part instanceof TInt) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }
            }

            return false;
        }

        if ($input_type_part instanceof TCallableString
            && (get_class($container_type_part) === TSingleLetter::class
                || get_class($container_type_part) === TNonEmptyString::class
                || get_class($container_type_part) === TNonFalsyString::class
                || get_class($container_type_part) === TLowercaseString::class)
        ) {
            return true;
        }

        if (($container_type_part instanceof TLowercaseString
                || $container_type_part instanceof TNonEmptyLowercaseString)
            && $input_type_part instanceof TString
        ) {
            if (($input_type_part instanceof TLowercaseString
                    && $container_type_part instanceof TLowercaseString)
                || ($input_type_part instanceof TNonEmptyLowercaseString
                    && $container_type_part instanceof TNonEmptyLowercaseString)
            ) {
                return true;
            }

            if ($input_type_part instanceof TNonEmptyLowercaseString
                && $container_type_part instanceof TLowercaseString
            ) {
                return true;
            }

            if ($input_type_part instanceof TLowercaseString
                && $container_type_part instanceof TNonEmptyLowercaseString
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            if ($input_type_part instanceof TLiteralString) {
                if (strtolower($input_type_part->value) === $input_type_part->value) {
                    return $input_type_part->value || $container_type_part instanceof TLowercaseString;
                }

                return false;
            }

            if ($input_type_part instanceof TClassString) {
                return false;
            }

            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TDependentGetClass) {
            $first_type = $container_type_part->as_type->getSingleAtomic();

            $container_type_part = new TClassString(
                'object',
                $first_type instanceof TNamedObject ? $first_type : null
            );
        }

        if ($input_type_part instanceof TDependentGetClass) {
            $first_type = $input_type_part->as_type->getSingleAtomic();

            if ($first_type instanceof TTemplateParam) {
                $object_type = $first_type->as->getSingleAtomic();

                $input_type_part = new TTemplateParamClass(
                    $first_type->param_name,
                    $first_type->as->getId(),
                    $object_type instanceof TNamedObject ? $object_type : null,
                    $first_type->defining_class
                );
            } else {
                $input_type_part = new TClassString(
                    'object',
                    $first_type instanceof TNamedObject ? $first_type : null
                );
            }
        }

        if ($input_type_part instanceof TDependentGetType) {
            $input_type_part = new TString();

            if ($container_type_part instanceof TLiteralString) {
                return isset(ClassLikeAnalyzer::GETTYPE_TYPES[$container_type_part->value]);
            }
        }

        if ($container_type_part instanceof TDependentGetDebugType) {
            return $input_type_part instanceof TString;
        }

        if ($input_type_part instanceof TDependentGetDebugType) {
            $input_type_part = new TString();
        }

        if ($container_type_part instanceof TDependentGetType) {
            $container_type_part = new TString();

            if ($input_type_part instanceof TLiteralString) {
                return isset(ClassLikeAnalyzer::GETTYPE_TYPES[$input_type_part->value]);
            }
        }

        if ($input_type_part instanceof TFalse
            && $container_type_part instanceof TBool
            && !($container_type_part instanceof TTrue)
        ) {
            return true;
        }

        if ($input_type_part instanceof TTrue
            && $container_type_part instanceof TBool
            && !($container_type_part instanceof TFalse)
        ) {
            return true;
        }

        // from https://wiki.php.net/rfc/scalar_type_hints_v5:
        //
        // > int types can resolve a parameter type of float
        if ($input_type_part instanceof TInt
            && $container_type_part instanceof TFloat
            && !$container_type_part instanceof TLiteralFloat
            && $allow_float_int_equality
        ) {
            return true;
        }

        if ($container_type_part instanceof TArrayKey
            && $input_type_part instanceof TNumeric
        ) {
            return true;
        }

        if ($container_type_part instanceof TArrayKey
            && ($input_type_part instanceof TInt
                || $input_type_part instanceof TString
                || $input_type_part instanceof TTemplateKeyOf)
        ) {
            return true;
        }

        if ($input_type_part instanceof TTemplateKeyOf) {
            foreach ($input_type_part->as->getAtomicTypes() as $atomic_type) {
                if ($atomic_type instanceof TArray) {
                    /** @var Scalar $array_key_atomic */
                    foreach ($atomic_type->type_params[0]->getAtomicTypes() as $array_key_atomic) {
                        if (!self::isContainedBy(
                            $codebase,
                            $array_key_atomic,
                            $container_type_part,
                            $allow_interface_equality,
                            $allow_float_int_equality,
                            $atomic_comparison_result
                        )) {
                            return false;
                        }
                    }
                }
            }

            return true;
        }

        if ($input_type_part instanceof TArrayKey &&
            ($container_type_part instanceof TInt || $container_type_part instanceof TString)
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_mixed = true;
                $atomic_comparison_result->scalar_type_match_found = true;
            }

            return false;
        }

        if ($container_type_part instanceof TScalar && $input_type_part instanceof Scalar) {
            return true;
        }

        if (get_class($container_type_part) === TDependentListKey::class
            && ($input_type_part instanceof TLiteralInt
                || $input_type_part instanceof TPositiveInt)
        ) {
            return true;
        }

        if (get_class($container_type_part) === TFloat::class && $input_type_part instanceof TLiteralFloat) {
            return true;
        }

        if ((get_class($container_type_part) === TNonEmptyString::class
                || get_class($container_type_part) === TNonEmptyNonspecificLiteralString::class)
            && $input_type_part instanceof TNonFalsyString
        ) {
            return true;
        }

        if ($container_type_part instanceof TNonFalsyString
            && $input_type_part instanceof TNonFalsyString
        ) {
            return true;
        }

        if ($container_type_part instanceof TNonFalsyString
            && ($input_type_part instanceof TNonEmptyString
                || $input_type_part instanceof TNonEmptyNonspecificLiteralString)
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TNonEmptyString
            && $input_type_part instanceof TLiteralString
            && $input_type_part->value === ''
        ) {
            return false;
        }

        if ($container_type_part instanceof TNonFalsyString
            && $input_type_part instanceof TLiteralString
            && $input_type_part->value === '0'
        ) {
            return false;
        }

        if ((get_class($container_type_part) === TNonEmptyString::class
                || get_class($container_type_part) === TNonFalsyString::class
                || get_class($container_type_part) === TSingleLetter::class)
            && $input_type_part instanceof TLiteralString
        ) {
            return true;
        }

        if (get_class($container_type_part) === TDependentListKey::class
            && $input_type_part instanceof TInt
        ) {
            return true;
        }

        if ((get_class($input_type_part) === TInt::class && $container_type_part instanceof TLiteralInt)
            || (get_class($input_type_part) === TPositiveInt::class
                && $container_type_part instanceof TLiteralInt
                && $container_type_part->value > 0)
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if ($input_type_part instanceof TIntRange && $container_type_part instanceof TIntRange) {
            return IntegerRangeComparator::isContainedBy(
                $input_type_part,
                $container_type_part
            );
        }

        if ($input_type_part instanceof TInt && $container_type_part instanceof TPositiveInt) {
            if ($input_type_part instanceof TPositiveInt) {
                return true;
            }
            if ($input_type_part instanceof TLiteralInt) {
                return $input_type_part->value > 0;
            }
            if ($input_type_part instanceof TIntRange) {
                return $input_type_part->isPositive();
            }

            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if ($input_type_part instanceof TInt && $container_type_part instanceof TIntRange) {
            if ($input_type_part instanceof TPositiveInt) {
                if ($container_type_part->min_bound > 1) {
                    //any positive int can't be pushed inside a range with a min > 1
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced = true;
                        $atomic_comparison_result->type_coerced_from_scalar = true;
                    }

                    return false;
                }

                if ($container_type_part->max_bound !== null) {
                    //any positive int can't be pushed inside a range where the max bound isn't max without coercion
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced = true;
                        $atomic_comparison_result->type_coerced_from_scalar = true;
                    }

                    return false;
                }

                return true;
            }
            if ($input_type_part instanceof TLiteralInt) {
                $min_bound = $container_type_part->min_bound;
                $max_bound = $container_type_part->max_bound;

                return
                    ($min_bound === null || $min_bound <= $input_type_part->value) &&
                    ($max_bound === null || $max_bound >= $input_type_part->value);
            }

            //any int can't be pushed inside a range without coercion (unless the range is from min to max)
            if ($container_type_part->min_bound !== null || $container_type_part->max_bound !== null) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                    $atomic_comparison_result->type_coerced_from_scalar = true;
                }
            }

            return false;
        }

        if (get_class($input_type_part) === TFloat::class && $container_type_part instanceof TLiteralFloat) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if ((get_class($input_type_part) === TString::class
                || get_class($input_type_part) === TSingleLetter::class
                || $input_type_part instanceof TNonEmptyString
                || $input_type_part instanceof TNonspecificLiteralString)
            && $container_type_part instanceof TLiteralString
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if (($input_type_part instanceof TLowercaseString
                || $input_type_part instanceof TNonEmptyLowercaseString)
            && $container_type_part instanceof TLiteralString
            && strtolower($container_type_part->value) === $container_type_part->value
        ) {
            if ($atomic_comparison_result
                && ($container_type_part->value)
            ) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_scalar = true;
            }

            return false;
        }

        if (($container_type_part instanceof TClassString || $container_type_part instanceof TLiteralClassString)
            && ($input_type_part instanceof TClassString || $input_type_part instanceof TLiteralClassString)
        ) {
            return ClassLikeStringComparator::isContainedBy(
                $codebase,
                $input_type_part,
                $container_type_part,
                $allow_interface_equality,
                $atomic_comparison_result
            );
        }

        if ($container_type_part instanceof TString && $input_type_part instanceof TTraitString) {
            return true;
        }

        if ($container_type_part instanceof TTraitString
            && (get_class($input_type_part) === TString::class
                || $input_type_part instanceof TNonEmptyString
                || $input_type_part instanceof TNonEmptyNonspecificLiteralString)
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if (($input_type_part instanceof TClassString
            || $input_type_part instanceof TLiteralClassString)
            && (get_class($container_type_part) === TSingleLetter::class
                || get_class($container_type_part) === TNonEmptyString::class
                || get_class($container_type_part) === TNonFalsyString::class)
        ) {
            return true;
        }

        if ($input_type_part instanceof TNumericString
            && get_class($container_type_part) === TNonEmptyString::class
        ) {
            return true;
        }

        if ($container_type_part instanceof TString
            && ($input_type_part instanceof TNumericString
                || $input_type_part instanceof THtmlEscapedString)
        ) {
            if ($container_type_part instanceof TLiteralString) {
                if (is_numeric($container_type_part->value) && $atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            return true;
        }

        if ($input_type_part instanceof TString
            && ($container_type_part instanceof TNumericString
                || $container_type_part instanceof THtmlEscapedString)
        ) {
            if ($input_type_part instanceof TLiteralString) {
                return is_numeric($input_type_part->value);
            }
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TCallableString
            && $input_type_part instanceof TLiteralString
        ) {
            $input_callable = CallableTypeComparator::getCallableFromAtomic($codebase, $input_type_part);
            $container_callable = CallableTypeComparator::getCallableFromAtomic($codebase, $container_type_part);

            if ($input_callable && $container_callable) {
                if (CallableTypeComparator::isContainedBy(
                    $codebase,
                    $input_callable,
                    $container_callable,
                    $atomic_comparison_result ?? new TypeComparisonResult()
                ) === false
                ) {
                    return false;
                }
            }

            if (!$input_callable) {
                //we could not find a callable for the input type, so the input is not contained in the container
                return false;
            }

            return true;
        }

        if ($input_type_part instanceof TLowercaseString
            && get_class($container_type_part) === TNonEmptyString::class) {
            return false;
        }

        if ($input_type_part->getKey() === $container_type_part->getKey()) {
            return true;
        }

        if (($container_type_part instanceof TClassString
                || $container_type_part instanceof TLiteralClassString
                || $container_type_part instanceof TCallableString)
            && $input_type_part instanceof TString
        ) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        if ($container_type_part instanceof TNumeric
            && $input_type_part->isNumericType()
        ) {
            return true;
        }

        if ($input_type_part instanceof TNumeric) {
            if ($container_type_part->isNumericType()) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->scalar_type_match_found = true;
                }
            }
        }

        if ($input_type_part instanceof Scalar) {
            if (!$container_type_part instanceof TLiteralInt
                && !$container_type_part instanceof TLiteralString
                && !$container_type_part instanceof TLiteralFloat
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->scalar_type_match_found = true;
                }
            }
        }

        return false;
    }
}
