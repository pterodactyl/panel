<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Union;

class MktimeReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [
            'mktime',
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        foreach ($call_args as $call_arg) {
            if (!($call_arg_type = $statements_source->node_data->getType($call_arg->value))
                || !$call_arg_type->isInt()
            ) {
                $value_type = new Union([new TInt, new TFalse]);

                $codebase = $statements_source->getCodebase();

                if ($codebase->config->ignore_internal_falsable_issues) {
                    $value_type->ignore_falsable_issues = true;
                }

                return $value_type;
            }
        }

        return Type::getInt();
    }
}
