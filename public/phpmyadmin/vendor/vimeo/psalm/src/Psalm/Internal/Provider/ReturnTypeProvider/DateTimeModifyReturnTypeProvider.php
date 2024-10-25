<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use DateTime;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

class DateTimeModifyReturnTypeProvider implements MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames(): array
    {
        return ['DateTime', 'DateTimeImmutable'];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $statements_source = $event->getSource();
        $call_args = $event->getCallArgs();
        $method_name_lowercase = $event->getMethodNameLowercase();
        if (!$statements_source instanceof StatementsAnalyzer
            || $method_name_lowercase !== 'modify'
            || !isset($call_args[0])
        ) {
            return null;
        }

        $first_arg = $call_args[0]->value;
        $first_arg_type = $statements_source->node_data->getType($first_arg);
        if (!$first_arg_type) {
            return null;
        }

        $has_date_time = false;
        $has_false = false;
        foreach ($first_arg_type->getAtomicTypes() as $type_part) {
            if (!$type_part instanceof TLiteralString) {
                return null;
            }

            if (@(new DateTime())->modify($type_part->value) === false) {
                $has_false = true;
            } else {
                $has_date_time = true;
            }
        }

        if ($has_false && !$has_date_time) {
            return Type::getFalse();
        }
        if ($has_date_time && !$has_false) {
            return Type::parseString($event->getFqClasslikeName());
        }

        return null;
    }
}
