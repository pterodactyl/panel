<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;
use UnexpectedValueException;

use function in_array;

class StrTrReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [
            'strtr',
        ];
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

        $type = Type::getString();

        if ($statements_source->data_flow_graph
            && !in_array('TaintedInput', $statements_source->getSuppressedIssues())) {
            $function_return_sink = DataFlowNode::getForMethodReturn(
                $function_id,
                $function_id,
                null,
                $code_location
            );

            $statements_source->data_flow_graph->addNode($function_return_sink);
            foreach ($call_args as $i => $_) {
                $function_param_sink = DataFlowNode::getForMethodArgument(
                    $function_id,
                    $function_id,
                    $i,
                    null,
                    $code_location
                );

                $statements_source->data_flow_graph->addNode($function_param_sink);

                $statements_source->data_flow_graph->addPath(
                    $function_param_sink,
                    $function_return_sink,
                    'arg'
                );
            }

            $type->parent_nodes = [$function_return_sink->id => $function_return_sink];
        }

        return $type;
    }
}
