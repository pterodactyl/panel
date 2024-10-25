<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Union;

class ArrayReverseReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_reverse'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg = $call_args[0]->value ?? null;

        $first_arg_array = $first_arg
            && ($first_arg_type = $statements_source->node_data->getType($first_arg))
            && $first_arg_type->hasType('array')
            && ($array_atomic_type = $first_arg_type->getAtomicTypes()['array'])
            && ($array_atomic_type instanceof TArray
                || $array_atomic_type instanceof TKeyedArray
                || $array_atomic_type instanceof TList)
        ? $array_atomic_type
        : null;

        if (!$first_arg_array) {
            return Type::getArray();
        }

        if ($first_arg_array instanceof TArray) {
            return new Union([clone $first_arg_array]);
        }

        if ($first_arg_array instanceof TList) {
            $second_arg = $call_args[1]->value ?? null;

            if (!$second_arg
                || (($second_arg_type = $statements_source->node_data->getType($second_arg))
                    && $second_arg_type->isFalse()
                )
            ) {
                return new Union([clone $first_arg_array]);
            }

            return new Union([new TArray([Type::getInt(), clone $first_arg_array->type_param])]);
        }

        return new Union([$first_arg_array->getGenericArrayType()]);
    }
}
