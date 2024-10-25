<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Union;

class InArrayReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['in_array'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $call_args = $event->getCallArgs();
        $bool = Type::getBool();

        if (!isset($call_args[0]) || !isset($call_args[1])) {
            return $bool;
        }

        $needle_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($call_args[0]->value);
        $haystack_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($call_args[1]->value);

        if ($needle_type === null || $haystack_type === null) {
            return $bool;
        }

        $false = Type::getFalse();
        $false->from_docblock = $bool->from_docblock = $needle_type->from_docblock || $haystack_type->from_docblock;

        if (!isset($call_args[2])) {
            return $bool;
        }

        $strict_type = $event->getStatementsSource()->getNodeTypeProvider()->getType($call_args[2]->value);

        if ($strict_type === null || !$strict_type->isTrue()) {
            return $bool;
        }

        /**
         * @var TKeyedArray|TArray|TList|null
         */
        $array_arg_type = ($types = $haystack_type->getAtomicTypes()) && isset($types['array'])
            ? $types['array']
            : null;

        if ($array_arg_type instanceof TKeyedArray) {
            $array_arg_type = $array_arg_type->getGenericArrayType();
        }

        if ($array_arg_type instanceof TList) {
            $array_arg_type = new TArray([Type::getInt(), $array_arg_type->type_param]);
        }

        if (!$array_arg_type instanceof TArray) {
            return $bool;
        }

        $haystack_item_type = $array_arg_type->type_params[1];

        if (UnionTypeComparator::canExpressionTypesBeIdentical(
            $event->getStatementsSource()->getCodebase(),
            $needle_type,
            $haystack_item_type
        )) {
            return $bool;
        }

        return $false;
    }
}
