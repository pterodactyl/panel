<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Union;

use function count;

class RandReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['rand', 'mt_rand', 'random_int'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        if (count($call_args) === 0) {
            return Type::getInt();
        }

        if (count($call_args) !== 2) {
            return null;
        }

        $statements_source = $event->getStatementsSource();
        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        $first_arg = $nodeTypeProvider->getType($call_args[0]->value);
        $second_arg = $nodeTypeProvider->getType($call_args[1]->value);

        $min_value = null;
        if ($first_arg !== null && $first_arg->isSingle()) {
            $first_atomic_type = $first_arg->getSingleAtomic();
            if ($first_atomic_type instanceof TLiteralInt) {
                $min_value = $first_atomic_type->value;
            } elseif ($first_atomic_type instanceof TIntRange) {
                $min_value = $first_atomic_type->min_bound;
            } elseif ($first_atomic_type instanceof TPositiveInt) {
                $min_value = 1;
            }
        }

        $max_value = null;
        if ($second_arg !== null && $second_arg->isSingle()) {
            $second_atomic_type = $second_arg->getSingleAtomic();
            if ($second_atomic_type instanceof TLiteralInt) {
                $max_value = $second_atomic_type->value;
            } elseif ($second_atomic_type instanceof TIntRange) {
                $max_value = $second_atomic_type->max_bound;
            } elseif ($second_atomic_type instanceof TPositiveInt) {
                // no max value, we keep null
            }
        }

        return new Union([new TIntRange($min_value, $max_value)]);
    }
}
