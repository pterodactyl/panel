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

class ArraySpliceReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_splice'];
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

        $already_cloned = false;

        if ($first_arg_array instanceof TKeyedArray) {
            $already_cloned = true;
            $first_arg_array = $first_arg_array->getGenericArrayType();
        }

        if ($first_arg_array instanceof TArray) {
            if (!$already_cloned) {
                $first_arg_array = clone $first_arg_array;
            }
            $array_type = new TArray($first_arg_array->type_params);
        } else {
            $array_type = new TArray([Type::getInt(), clone $first_arg_array->type_param]);
        }

        if (!$array_type->type_params[0]->hasString()) {
            if ($array_type->type_params[1]->isString()) {
                $array_type = new TList(Type::getString());
            } elseif ($array_type->type_params[1]->isInt()) {
                $array_type = new TList(Type::getInt());
            } else {
                $array_type = new TList(Type::getMixed());
            }
        }

        return new Union([$array_type]);
    }
}
