<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_merge;
use function array_shift;

class ArraySliceReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_slice'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();

        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg = $call_args[0]->value ?? null;

        if (!$first_arg) {
            return Type::getArray();
        }

        $first_arg_type = $statements_source->node_data->getType($first_arg);

        if (!$first_arg_type) {
            return Type::getArray();
        }

        $atomic_types = $first_arg_type->getAtomicTypes();

        $return_atomic_type = null;

        while ($atomic_type = array_shift($atomic_types)) {
            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $atomic_type->as->getAtomicTypes());
                continue;
            }

            $already_cloned = false;

            if ($atomic_type instanceof TKeyedArray) {
                $already_cloned = true;
                $atomic_type = $atomic_type->getGenericArrayType();
            }

            if ($atomic_type instanceof TArray) {
                if (!$already_cloned) {
                    $atomic_type = clone $atomic_type;
                }

                $return_atomic_type = new TArray($atomic_type->type_params);
                continue;
            }

            if ($atomic_type instanceof TList) {
                $return_atomic_type = new TArray([Type::getInt(), clone $atomic_type->type_param]);
                continue;
            }

            return Type::getArray();
        }

        if (!$return_atomic_type) {
            throw new UnexpectedValueException('This should never happen');
        }

        $dont_preserve_int_keys = !isset($call_args[3]->value)
            || (($third_arg_type = $statements_source->node_data->getType($call_args[3]->value))
                && ((string) $third_arg_type === 'false'));

        if ($dont_preserve_int_keys && $return_atomic_type->type_params[0]->isInt()) {
            $return_atomic_type = new TList($return_atomic_type->type_params[1]);
        }

        return new Union([$return_atomic_type]);
    }
}
