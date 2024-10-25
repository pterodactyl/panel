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
use Psalm\Type\Union;

use function count;
use function reset;

class ArrayColumnReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_column'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer
            || count($call_args) < 2
        ) {
            return Type::getMixed();
        }

        $row_type = $row_shape = null;
        $input_array_not_empty = false;

        // calculate row shape
        if (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))
            && $first_arg_type->isSingle()
            && $first_arg_type->hasArray()
        ) {
            $input_array = $first_arg_type->getAtomicTypes()['array'];
            if ($input_array instanceof TKeyedArray) {
                $row_type = $input_array->getGenericArrayType()->type_params[1];
            } elseif ($input_array instanceof TArray) {
                $row_type = $input_array->type_params[1];
            } elseif ($input_array instanceof TList) {
                $row_type = $input_array->type_param;
            }

            if ($row_type && $row_type->isSingle()) {
                if ($row_type->hasArray()) {
                    $row_shape = $row_type->getAtomicTypes()['array'];
                } elseif ($row_type->hasObjectType()) {
                    $row_shape_union = GetObjectVarsReturnTypeProvider::getGetObjectVarsReturnType(
                        $row_type,
                        $statements_source,
                        $event->getContext(),
                        $event->getCodeLocation()
                    );
                    if ($row_shape_union->isSingle()) {
                        $row_shape_union_parts = $row_shape_union->getAtomicTypes();
                        $row_shape = reset($row_shape_union_parts);
                    }
                }
            }

            $input_array_not_empty = $input_array instanceof TNonEmptyList ||
                $input_array instanceof TNonEmptyArray ||
                $input_array instanceof TKeyedArray;
        }

        $value_column_name = null;
        $value_column_name_is_null = false;
        // calculate value column name
        if (($second_arg_type = $statements_source->node_data->getType($call_args[1]->value))) {
            if ($second_arg_type->isSingleIntLiteral()) {
                $value_column_name = $second_arg_type->getSingleIntLiteral()->value;
            } elseif ($second_arg_type->isSingleStringLiteral()) {
                $value_column_name = $second_arg_type->getSingleStringLiteral()->value;
            }
            $value_column_name_is_null = $second_arg_type->isNull();
        }

        $key_column_name = null;
        $third_arg_type = null;
        // calculate key column name
        if (isset($call_args[2])) {
            $third_arg_type = $statements_source->node_data->getType($call_args[2]->value);

            if ($third_arg_type) {
                if ($third_arg_type->isSingleIntLiteral()) {
                    $key_column_name = $third_arg_type->getSingleIntLiteral()->value;
                } elseif ($third_arg_type->isSingleStringLiteral()) {
                    $key_column_name = $third_arg_type->getSingleStringLiteral()->value;
                }
            }
        }

        $result_key_type = Type::getArrayKey();
        $result_element_type = null !== $row_type && $value_column_name_is_null ? $row_type : null;
        $have_at_least_one_res = false;
        // calculate results
        if ($row_shape instanceof TKeyedArray) {
            if ((null !== $value_column_name) && isset($row_shape->properties[$value_column_name])) {
                $result_element_type = $row_shape->properties[$value_column_name];
                // When the selected key is possibly_undefined, the resulting array can be empty
                if ($input_array_not_empty && $result_element_type->possibly_undefined !== true) {
                    $have_at_least_one_res = true;
                }
                //array_column skips undefined elements so resulting type is necessarily defined
                $result_element_type->possibly_undefined = false;
            } elseif (!$value_column_name_is_null) {
                $result_element_type = Type::getMixed();
            }

            if ((null !== $key_column_name) && isset($row_shape->properties[$key_column_name])) {
                $result_key_type = $row_shape->properties[$key_column_name];
            }
        }

        if (isset($call_args[2]) && (string)$third_arg_type !== 'null') {
            $type = $have_at_least_one_res ?
                new TNonEmptyArray([$result_key_type, $result_element_type ?? Type::getMixed()])
                : new TArray([$result_key_type, $result_element_type ?? Type::getMixed()]);
        } else {
            $type = $have_at_least_one_res ?
                new TNonEmptyList($result_element_type ?? Type::getMixed())
                : new TList($result_element_type ?? Type::getMixed());
        }

        return new Union([$type]);
    }
}
