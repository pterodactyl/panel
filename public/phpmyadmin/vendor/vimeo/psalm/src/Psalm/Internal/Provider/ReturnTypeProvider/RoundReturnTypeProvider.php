<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;

use function array_values;
use function count;
use function round;

use const PHP_ROUND_HALF_UP;

/**
 * @internal
 */
class RoundReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['round'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) === 0) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        $num_arg = $nodeTypeProvider->getType($call_args[0]->value);

        $precision_val = 0;
        if ($statements_source instanceof StatementsAnalyzer && count($call_args) > 1) {
            $type = $statements_source->node_data->getType($call_args[1]->value);

            if ($type !== null && $type->isSingle()) {
                $atomic_type = array_values($type->getAtomicTypes())[0];
                if ($atomic_type instanceof Type\Atomic\TLiteralInt) {
                    $precision_val = $atomic_type->value;
                }
            }
        }

        $mode_val = PHP_ROUND_HALF_UP;
        if ($statements_source instanceof StatementsAnalyzer && count($call_args) > 2) {
            $type = $statements_source->node_data->getType($call_args[2]->value);

            if ($type !== null && $type->isSingle()) {
                $atomic_type = array_values($type->getAtomicTypes())[0];
                if ($atomic_type instanceof Type\Atomic\TLiteralInt) {
                    /** @var positive-int|0 $mode_val */
                    $mode_val = $atomic_type->value;
                }
            }
        }

        if ($num_arg !== null && $num_arg->isSingle()) {
            $num_type = array_values($num_arg->getAtomicTypes())[0];
            if ($num_type instanceof Type\Atomic\TLiteralFloat || $num_type instanceof Type\Atomic\TLiteralInt) {
                $rounded_val = round($num_type->value, $precision_val, $mode_val);
                return new Type\Union([new Type\Atomic\TLiteralFloat($rounded_val)]);
            }
        }

        return new Type\Union([new Type\Atomic\TFloat()]);
    }
}
