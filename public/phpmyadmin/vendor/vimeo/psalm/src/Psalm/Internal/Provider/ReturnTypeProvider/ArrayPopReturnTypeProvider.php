<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

class ArrayPopReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_pop', 'array_shift'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg = $call_args[0]->value ?? null;

        $first_arg_array = $first_arg
            && ($first_arg_type = $statements_source->node_data->getType($first_arg))
            && $first_arg_type->hasType('array')
            && !$first_arg_type->hasMixed()
            && ($array_atomic_type = $first_arg_type->getAtomicTypes()['array'])
            && ($array_atomic_type instanceof TArray
                || $array_atomic_type instanceof TKeyedArray
                || $array_atomic_type instanceof TList)
        ? $array_atomic_type
        : null;

        if (!$first_arg_array) {
            return Type::getMixed();
        }

        $nullable = false;

        if ($first_arg_array instanceof TArray) {
            $value_type = clone $first_arg_array->type_params[1];

            if ($value_type->isEmpty()) {
                return Type::getNull();
            }

            if (!$first_arg_array instanceof TNonEmptyArray) {
                $nullable = true;
            }
        } elseif ($first_arg_array instanceof TList) {
            $value_type = clone $first_arg_array->type_param;

            if (!$first_arg_array instanceof TNonEmptyList) {
                $nullable = true;
            }
        } else {
            // special case where we know the type of the first element
            if ($function_id === 'array_shift' && $first_arg_array->is_list && isset($first_arg_array->properties[0])) {
                $value_type = clone $first_arg_array->properties[0];
            } else {
                $value_type = $first_arg_array->getGenericValueType();

                if (!$first_arg_array->sealed && !$first_arg_array->previous_value_type) {
                    $nullable = true;
                }
            }
        }

        if ($nullable) {
            $value_type->addType(new TNull);

            $codebase = $statements_source->getCodebase();

            if ($codebase->config->ignore_internal_nullable_issues) {
                $value_type->ignore_nullable_issues = true;
            }
        }

        return $value_type;
    }
}
