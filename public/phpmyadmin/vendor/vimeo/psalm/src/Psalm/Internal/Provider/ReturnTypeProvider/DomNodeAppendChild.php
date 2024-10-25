<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Union;

class DomNodeAppendChild implements MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames(): array
    {
        return ['DomNode'];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $source = $event->getSource();
        $call_args = $event->getCallArgs();
        $method_name_lowercase = $event->getMethodNameLowercase();

        if ($method_name_lowercase !== 'appendchild') {
            return null;
        }

        if (!$source instanceof StatementsAnalyzer
            || !$call_args
        ) {
            return Type::getMixed();
        }

        if (($first_arg_type = $source->node_data->getType($call_args[0]->value))
            && $first_arg_type->hasObjectType()
        ) {
            return clone $first_arg_type;
        }

        return null;
    }
}
