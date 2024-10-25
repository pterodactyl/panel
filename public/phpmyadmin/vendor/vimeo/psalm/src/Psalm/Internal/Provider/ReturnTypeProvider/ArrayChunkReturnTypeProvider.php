<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Type\ArrayType;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Union;

use function count;

class ArrayChunkReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_chunk'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $call_args = $event->getCallArgs();
        $statements_source = $event->getStatementsSource();
        if (count($call_args) >= 2
            && ($array_arg_type = $statements_source->getNodeTypeProvider()->getType($call_args[0]->value))
            && $array_arg_type->isSingle()
            && $array_arg_type->hasArray()
            && ($array_type = ArrayType::infer($array_arg_type->getAtomicTypes()['array']))
        ) {
            $preserve_keys = isset($call_args[2])
                && ($preserve_keys_arg_type = $statements_source->getNodeTypeProvider()->getType($call_args[2]->value))
                && (string) $preserve_keys_arg_type !== 'false';

            return new Union([
                new TList(
                    new Union([
                        $preserve_keys
                            ? new TNonEmptyArray([$array_type->key, $array_type->value])
                            : new TNonEmptyList($array_type->value)
                    ])
                )
            ]);
        }

        return new Union([new TList(Type::getArray())]);
    }
}
