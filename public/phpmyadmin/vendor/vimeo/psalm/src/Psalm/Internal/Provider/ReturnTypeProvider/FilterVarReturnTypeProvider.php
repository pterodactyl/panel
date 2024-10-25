<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;
use UnexpectedValueException;

use function in_array;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_DOMAIN;
use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;
use const FILTER_VALIDATE_IP;
use const FILTER_VALIDATE_MAC;
use const FILTER_VALIDATE_REGEXP;
use const FILTER_VALIDATE_URL;

class FilterVarReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['filter_var'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        $code_location = $event->getCodeLocation();
        if (!$statements_source instanceof StatementsAnalyzer) {
            throw new UnexpectedValueException();
        }

        $filter_type = null;

        if (isset($call_args[1])
            && ($second_arg_type = $statements_source->node_data->getType($call_args[1]->value))
            && $second_arg_type->isSingleIntLiteral()
        ) {
            $filter_type_type = $second_arg_type->getSingleIntLiteral();

            switch ($filter_type_type->value) {
                case FILTER_VALIDATE_INT:
                    $filter_type = Type::getInt();
                    break;

                case FILTER_VALIDATE_FLOAT:
                    $filter_type = Type::getFloat();
                    break;

                case FILTER_VALIDATE_BOOLEAN:
                    $filter_type = Type::getBool();

                    break;

                case FILTER_VALIDATE_IP:
                case FILTER_VALIDATE_MAC:
                case FILTER_VALIDATE_REGEXP:
                case FILTER_VALIDATE_URL:
                case FILTER_VALIDATE_EMAIL:
                case FILTER_VALIDATE_DOMAIN:
                    $filter_type = Type::getString();
                    break;
            }

            $has_object_like = false;
            $filter_null = false;

            if (isset($call_args[2])
                && ($third_arg_type = $statements_source->node_data->getType($call_args[2]->value))
                && $filter_type
            ) {
                foreach ($third_arg_type->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TKeyedArray) {
                        $has_object_like = true;

                        if (isset($atomic_type->properties['options'])
                            && $atomic_type->properties['options']->hasArray()
                            && ($options_array = $atomic_type->properties['options']->getAtomicTypes()['array'] ?? null)
                            && $options_array instanceof TKeyedArray
                            && isset($options_array->properties['default'])
                        ) {
                            $filter_type = Type::combineUnionTypes(
                                $filter_type,
                                $options_array->properties['default']
                            );
                        } else {
                            $filter_type->addType(new TFalse);
                        }

                        if (isset($atomic_type->properties['flags'])
                            && $atomic_type->properties['flags']->isSingleIntLiteral()
                        ) {
                            $filter_flag_type =
                                $atomic_type->properties['flags']->getSingleIntLiteral();

                            if ($filter_type->hasBool()
                                && $filter_flag_type->value === FILTER_NULL_ON_FAILURE
                            ) {
                                $filter_type->addType(new TNull);
                            }
                        }
                    } elseif ($atomic_type instanceof TLiteralInt) {
                        if ($atomic_type->value === FILTER_NULL_ON_FAILURE) {
                            $filter_null = true;
                            $filter_type->addType(new TNull);
                        }
                    }
                }
            }

            if (!$has_object_like && !$filter_null && $filter_type) {
                $filter_type->addType(new TFalse);
            }
        }

        if (!$filter_type) {
            $filter_type = Type::getMixed();
        }

        if ($statements_source->data_flow_graph
            && !in_array('TaintedInput', $statements_source->getSuppressedIssues())
        ) {
            $function_return_sink = DataFlowNode::getForMethodReturn(
                $function_id,
                $function_id,
                null,
                $code_location
            );

            $statements_source->data_flow_graph->addNode($function_return_sink);

            $function_param_sink = DataFlowNode::getForMethodArgument(
                $function_id,
                $function_id,
                0,
                null,
                $code_location
            );

            $statements_source->data_flow_graph->addNode($function_param_sink);

            $statements_source->data_flow_graph->addPath(
                $function_param_sink,
                $function_return_sink,
                'arg'
            );

            $filter_type->parent_nodes = [$function_return_sink->id => $function_return_sink];
        }

        return $filter_type;
    }
}
