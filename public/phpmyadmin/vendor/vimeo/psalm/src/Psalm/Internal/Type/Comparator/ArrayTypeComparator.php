<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Union;

use function array_map;
use function range;

/**
 * @internal
 */
class ArrayTypeComparator
{
    /**
     * @param TArray|TKeyedArray|TList|TClassStringMap $input_type_part
     * @param TArray|TKeyedArray|TList|TClassStringMap $container_type_part
     */
    public static function isContainedBy(
        Codebase $codebase,
        Atomic $input_type_part,
        Atomic $container_type_part,
        bool $allow_interface_equality,
        ?TypeComparisonResult $atomic_comparison_result
    ): bool {
        $all_types_contain = true;

        $is_empty_array = $input_type_part->equals(
            new TArray([
                new Union([new TEmpty()]),
                new Union([new TEmpty()])
            ]),
            false
        );

        if ($is_empty_array
            && (($container_type_part instanceof TArray
                    && !$container_type_part instanceof TNonEmptyArray)
                || ($container_type_part instanceof TKeyedArray
                    && !$container_type_part->isNonEmpty())
            )
        ) {
            return true;
        }

        if ($container_type_part instanceof TKeyedArray
            && $input_type_part instanceof TArray
        ) {
            $all_string_int_literals = true;

            $properties = [];

            foreach ($input_type_part->type_params[0]->getAtomicTypes() as $atomic_key_type) {
                if ($atomic_key_type instanceof TLiteralString || $atomic_key_type instanceof TLiteralInt) {
                    $properties[$atomic_key_type->value] = clone $input_type_part->type_params[1];
                    $properties[$atomic_key_type->value]->possibly_undefined = true;
                } else {
                    $all_string_int_literals = false;
                }
            }

            if ($all_string_int_literals && $properties) {
                $input_type_part = new TKeyedArray($properties);

                return KeyedArrayComparator::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $atomic_comparison_result
                );
            }
        }

        if ($container_type_part instanceof TList
            && $input_type_part instanceof TKeyedArray
        ) {
            if ($input_type_part->is_list) {
                $input_type_part = $input_type_part->getList();
            } else {
                return false;
            }
        }

        if ($container_type_part instanceof TList
            && $input_type_part instanceof TClassStringMap
        ) {
            return false;
        }

        if ($container_type_part instanceof TList
            && $input_type_part instanceof TArray
            && $input_type_part->type_params[1]->isEmpty()
        ) {
            return !$container_type_part instanceof TNonEmptyList;
        }

        if ($container_type_part instanceof TNonEmptyList
            && $input_type_part instanceof TNonEmptyArray
            && $input_type_part->type_params[0]->isSingleIntLiteral()
            && $input_type_part->type_params[0]->getSingleIntLiteral()->value === 0
        ) {
            //this is a special case where the only offset value of an non empty array is 0, so it's a non empty list
            return UnionTypeComparator::isContainedBy(
                $codebase,
                $input_type_part->type_params[1],
                $container_type_part->type_param,
                $input_type_part->type_params[1]->ignore_nullable_issues,
                $input_type_part->type_params[1]->ignore_falsable_issues,
                $atomic_comparison_result,
                $allow_interface_equality
            );
        }

        if ($input_type_part instanceof TList
            && $container_type_part instanceof TList
        ) {
            if (!UnionTypeComparator::isContainedBy(
                $codebase,
                $input_type_part->type_param,
                $container_type_part->type_param,
                $input_type_part->type_param->ignore_nullable_issues,
                $input_type_part->type_param->ignore_falsable_issues,
                $atomic_comparison_result,
                $allow_interface_equality
            )) {
                return false;
            }

            return $input_type_part instanceof TNonEmptyList
                || !$container_type_part instanceof TNonEmptyList;
        }

        if ($container_type_part instanceof TKeyedArray) {
            if ($container_type_part->is_list) {
                $container_type_part = $container_type_part->getList();

                return self::isContainedBy(
                    $codebase,
                    $input_type_part,
                    $container_type_part,
                    $allow_interface_equality,
                    $atomic_comparison_result
                );
            }

            $container_type_part = $container_type_part->getGenericArrayType();
        }

        if ($input_type_part instanceof TKeyedArray) {
            $input_type_part = $input_type_part->getGenericArrayType();
        }

        if ($input_type_part instanceof TClassStringMap) {
            $input_type_part = new TArray([
                $input_type_part->getStandinKeyParam(),
                clone $input_type_part->value_param
            ]);
        }

        if ($container_type_part instanceof TClassStringMap) {
            $container_type_part = new TArray([
                $container_type_part->getStandinKeyParam(),
                clone $container_type_part->value_param
            ]);
        }

        if ($container_type_part instanceof TList) {
            $all_types_contain = false;

            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            $container_type_part = new TArray([Type::getInt(), clone $container_type_part->type_param]);
        }

        if ($input_type_part instanceof TList) {
            if ($input_type_part instanceof TNonEmptyList) {
                // if the array has a known size < 10, make sure the array keys are literal ints
                if ($input_type_part->count !== null && $input_type_part->count < 10) {
                    $literal_ints = array_map(
                        function ($i) {
                            return new TLiteralInt($i);
                        },
                        range(0, $input_type_part->count - 1)
                    );

                    $input_type_part = new TNonEmptyArray([
                        new Union($literal_ints),
                        clone $input_type_part->type_param
                    ]);
                } else {
                    $input_type_part = new TNonEmptyArray([Type::getInt(), clone $input_type_part->type_param]);
                }
            } else {
                $input_type_part = new TArray([Type::getInt(), clone $input_type_part->type_param]);
            }
        }

        foreach ($input_type_part->type_params as $i => $input_param) {
            if ($i > 1) {
                break;
            }

            $container_param = $container_type_part->type_params[$i];

            if ($i === 0
                && $input_param->hasMixed()
                && $container_param->hasString()
                && $container_param->hasInt()
            ) {
                continue;
            }

            if ($input_param->isEmpty()
                && $container_type_part instanceof TNonEmptyArray
            ) {
                return false;
            }

            $param_comparison_result = new TypeComparisonResult();

            if (!$input_param->isEmpty()) {
                if (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_param,
                    $container_param,
                    $input_param->ignore_nullable_issues,
                    $input_param->ignore_falsable_issues,
                    $param_comparison_result,
                    $allow_interface_equality
                )) {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->type_coerced
                            = $param_comparison_result->type_coerced === true
                                && $atomic_comparison_result->type_coerced !== false;

                        $atomic_comparison_result->type_coerced_from_mixed
                            = $param_comparison_result->type_coerced_from_mixed === true
                                && $atomic_comparison_result->type_coerced_from_mixed !== false;

                        $atomic_comparison_result->type_coerced_from_as_mixed
                            = $param_comparison_result->type_coerced_from_as_mixed === true
                                && $atomic_comparison_result->type_coerced_from_as_mixed !== false;

                        $atomic_comparison_result->type_coerced_from_scalar
                            = $param_comparison_result->type_coerced_from_scalar === true
                                && $atomic_comparison_result->type_coerced_from_scalar !== false;

                        $atomic_comparison_result->scalar_type_match_found
                            = $param_comparison_result->scalar_type_match_found === true
                                && $atomic_comparison_result->scalar_type_match_found !== false;
                    }

                    if (!$param_comparison_result->type_coerced_from_as_mixed) {
                        $all_types_contain = false;
                    }
                } else {
                    if ($atomic_comparison_result) {
                        $atomic_comparison_result->to_string_cast
                            = $atomic_comparison_result->to_string_cast === true
                                || $param_comparison_result->to_string_cast === true;
                    }
                }
            }
        }

        if ($container_type_part instanceof TNonEmptyArray
            && !$input_type_part instanceof TNonEmptyArray
        ) {
            if ($all_types_contain && $atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
            }

            return false;
        }

        return $all_types_contain;
    }
}
