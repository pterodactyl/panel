<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Type\ArrayType;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TDependentListKey;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Union;

use function array_filter;
use function assert;
use function count;
use function in_array;
use function max;
use function min;

class MinMaxReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['min', 'max'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) === 0) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        if (count($call_args) === 1
            && ($array_arg_type = $nodeTypeProvider->getType($call_args[0]->value))
            && $array_arg_type->isSingle()
            && $array_arg_type->hasArray()
            && ($array_type = ArrayType::infer($array_arg_type->getAtomicTypes()['array']))
        ) {
            return $array_type->value;
        }

        $all_int = true;
        $min_bounds = [];
        $max_bounds = [];
        foreach ($call_args as $arg) {
            if ($array_arg_type = $nodeTypeProvider->getType($arg->value)) {
                foreach ($array_arg_type->getAtomicTypes() as $atomic_type) {
                    if (!$atomic_type instanceof TInt) {
                        $all_int = false;
                        break;
                    }

                    if ($atomic_type instanceof TLiteralInt) {
                        $min_bounds[] = $atomic_type->value;
                        $max_bounds[] = $atomic_type->value;
                    } elseif ($atomic_type instanceof TIntRange) {
                        $min_bounds[] = $atomic_type->min_bound;
                        $max_bounds[] = $atomic_type->max_bound;
                    } elseif ($atomic_type instanceof TPositiveInt) {
                        $min_bounds[] = 1;
                        $max_bounds[] = null;
                    } elseif ($atomic_type instanceof TDependentListKey) {
                        $min_bounds[] = 0;
                        $max_bounds[] = null;
                    } else {//already guarded by the `instanceof TInt` check above
                        $min_bounds[] = null;
                        $max_bounds[] = null;
                    }
                }
            } else {
                return Type::getMixed();
            }
        }

        if ($all_int) {
            if ($event->getFunctionId() === 'min') {
                assert(count($min_bounds) !== 0);
                //null values in $max_bounds doesn't make sense for min() so we remove them
                $max_bounds = array_filter($max_bounds, function ($v) {
                    return $v !== null;
                }) ?: [null];

                $min_potential_int = in_array(null, $min_bounds, true) ? null : min($min_bounds);
                $max_potential_int = in_array(null, $max_bounds, true) ? null : min($max_bounds);
            } else {
                assert(count($max_bounds) !== 0);
                //null values in $min_bounds doesn't make sense for max() so we remove them
                $min_bounds = array_filter($min_bounds, function ($v) {
                    return $v !== null;
                }) ?: [null];

                $min_potential_int = in_array(null, $min_bounds, true) ? null : max($min_bounds);
                $max_potential_int = in_array(null, $max_bounds, true) ? null : max($max_bounds);
            }

            if ($min_potential_int === null && $max_potential_int === null) {
                return Type::getInt();
            }

            if ($min_potential_int === $max_potential_int) {
                return Type::getInt(false, $min_potential_int);
            }

            return new Union([new TIntRange($min_potential_int, $max_potential_int)]);
        }

        //if we're dealing with non-int elements, just combine them all together
        $return_type = null;
        foreach ($call_args as $arg) {
            if ($array_arg_type = $nodeTypeProvider->getType($arg->value)) {
                if ($array_arg_type->isSingle()) {
                    $atomic_type = $array_arg_type->getSingleAtomic();
                    if ($atomic_type instanceof TPositiveInt) {
                        //we replace TPositiveInt with a range for better combination
                        $array_arg_type->removeType('int');
                        $array_arg_type->addType(new TIntRange(1, null));
                    }
                }

                $return_type = Type::combineUnionTypes(
                    $return_type,
                    $array_arg_type
                );
            } else {
                return Type::getMixed();
            }
        }

        return $return_type;
    }
}
