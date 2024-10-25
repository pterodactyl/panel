<?php

namespace Psalm\Internal\Type\Comparator;

use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Union;
use UnexpectedValueException;

use function count;
use function get_class;

/**
 * @internal
 */
class IntegerRangeComparator
{
    /**
     * This method is used to check if an integer range can be contained in another
     */
    public static function isContainedBy(
        TIntRange $input_type_part,
        TIntRange $container_type_part
    ): bool {
        $is_input_min = $input_type_part->min_bound === null;
        $is_input_max = $input_type_part->max_bound === null;
        $is_container_min = $container_type_part->min_bound === null;
        $is_container_max = $container_type_part->max_bound === null;

        $is_input_min_in_container = (
                $is_container_min ||
                (!$is_input_min && $container_type_part->min_bound <= $input_type_part->min_bound)
            );
        $is_input_max_in_container = (
                $is_container_max ||
                (!$is_input_max && $container_type_part->max_bound >= $input_type_part->max_bound)
            );
        return $is_input_min_in_container && $is_input_max_in_container;
    }

    /**
     * This method is used to check if an integer range can be contained by multiple int types
     * Worst case scenario, the input is `int<-50,max>` and container is `-50|int<-49,50>|positive-int|57`
     */
    public static function isContainedByUnion(
        TIntRange $input_type_part,
        Union $container_type
    ): bool {
        $container_atomic_types = $container_type->getAtomicTypes();
        $reduced_range = clone $input_type_part;

        if (isset($container_atomic_types['int'])) {
            if (get_class($container_atomic_types['int']) === TInt::class) {
                return true;
            }

            if (get_class($container_atomic_types['int']) === TNonspecificLiteralInt::class) {
                return true;
            }

            if (get_class($container_atomic_types['int']) === TPositiveInt::class) {
                if ($input_type_part->isPositive()) {
                    return true;
                }

                //every positive integer is satisfied by the positive-int int container so we reduce the range
                $reduced_range->max_bound = 0;
                unset($container_atomic_types['int']);
            } else {
                throw new UnexpectedValueException('Should not happen: unknown int key');
            }
        }

        $new_nb_atomics = count($container_atomic_types);
        //loop until we get to a stable situation. Either we can't remove atomics or we have a definite result
        do {
            $nb_atomics = $new_nb_atomics;
            $result_reduction = self::reduceRangeIncrementally($container_atomic_types, $reduced_range);
            $new_nb_atomics = count($container_atomic_types);
        } while ($result_reduction === null && $nb_atomics !== $new_nb_atomics);

        if ($result_reduction === null && $nb_atomics === 0) {
            //the range could not be reduced enough and there is no more atomics, it's not contained
            return false;
        }

        return $result_reduction ?? false;
    }

    /**
     * This method receives an array of atomics from the container and a range.
     * The goal is to use values in atomics in order to reduce the range.
     * Once the range is empty, it means that every value in range was covered by some atomics combination
     * @param array<string, Atomic> $container_atomic_types
     */
    private static function reduceRangeIncrementally(array &$container_atomic_types, TIntRange $reduced_range): ?bool
    {
        foreach ($container_atomic_types as $key => $container_atomic_type) {
            if ($container_atomic_type instanceof TIntRange) {
                if (self::isContainedBy($reduced_range, $container_atomic_type)) {
                    if ($container_atomic_type->max_bound === null && $container_atomic_type->min_bound === null) {
                        //this container range covers any integer
                        return true;
                    }
                    if ($container_atomic_type->max_bound === null) {
                        //this container range is int<X, max>
                        //X-1 becomes the max of our reduced range if it was higher
                        $reduced_range->max_bound = TIntRange::getNewLowestBound(
                            $container_atomic_type->min_bound - 1,
                            $reduced_range->max_bound ?? $container_atomic_type->min_bound - 1
                        );
                        unset($container_atomic_types[$key]); //we don't need this one anymore
                        continue;
                    }
                    if ($container_atomic_type->min_bound === null) {
                        //this container range is int<min, X>
                        //X+1 becomes the min of our reduced range if it was lower
                        $reduced_range->min_bound = TIntRange::getNewHighestBound(
                            $container_atomic_type->max_bound + 1,
                            $reduced_range->min_bound ?? $container_atomic_type->max_bound + 1
                        );
                        unset($container_atomic_types[$key]); //we don't need this one anymore
                        continue;
                    }
                    //if the container range has no 'null' bound, it's more complex
                    //in this case, we can only reduce if the container include one bound of our reduced range
                    if ($reduced_range->min_bound !== null
                        && $container_atomic_type->contains($reduced_range->min_bound)
                    ) {
                        //this container range is int<X, Y> and contains the min of our reduced range.
                        //the min from our reduced range becomes Y + 1
                        $reduced_range->min_bound = $container_atomic_type->max_bound + 1;
                        unset($container_atomic_types[$key]); //we don't need this one anymore
                    } elseif ($reduced_range->max_bound !== null
                        && $container_atomic_type->contains($reduced_range->max_bound)) {
                        //this container range is int<X, Y> and contains the max of our reduced range.
                        //the max from our reduced range becomes X - 1
                        $reduced_range->max_bound = $container_atomic_type->min_bound - 1;
                        unset($container_atomic_types[$key]); //we don't need this one anymore
                    }
                    //there is probably a case here where we could unset containers when they're not at all in our range
                } else {
                    //the range in input is wider than container, we return false
                    return false;
                }
            } elseif ($container_atomic_type instanceof TLiteralInt) {
                if (!$reduced_range->contains($container_atomic_type->value)) {
                    unset($container_atomic_types[$key]); //we don't need this one anymore
                } elseif ($reduced_range->min_bound === $container_atomic_type->value) {
                    $reduced_range->min_bound++;
                    unset($container_atomic_types[$key]); //we don't need this one anymore
                } elseif ($reduced_range->max_bound === $container_atomic_type->value) {
                    $reduced_range->max_bound--;
                    unset($container_atomic_types[$key]); //we don't need this one anymore
                }
            }
        }

        //there is probably a case here if we're left only with TLiteralInt where we could return false if there's less
        //of them than numbers in the reduced range

        //there is also a case where if there's not TLiteralInt anymore and we're left with TIntRange that don't contain
        //bounds from our reduced range where we could return false

        //if our reduced range has its min bound superior to its max bound, it means the container covers it all.
        if ($reduced_range->min_bound !== null &&
            $reduced_range->max_bound !== null &&
            $reduced_range->min_bound > $reduced_range->max_bound
        ) {
            return true;
        }

        //if we didn't return true or false before then the result is inconclusive for this round
        return null;
    }
}
