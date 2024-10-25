<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Union;

use function assert;
use function in_array;

class ImagickPixelColorReturnTypeProvider implements MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames(): array
    {
        return ['imagickpixel'];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $source = $event->getSource();
        $call_args = $event->getCallArgs();
        $method_name_lowercase = $event->getMethodNameLowercase();

        if ($method_name_lowercase !== 'getcolor') {
            return null;
        }

        if (!$source instanceof StatementsAnalyzer) {
            return null;
        }

        if (!$call_args) {
            $formats = [0 => true];
        } else {
            $normalized = $source->node_data->getType($call_args[0]->value) ?? Type::getMixed();
            $formats = [];
            foreach ($normalized->getAtomicTypes() as $t) {
                if ($t instanceof TLiteralInt && in_array($t->value, [0, 1, 2], true)) {
                    $formats[$t->value] = true;
                } else {
                    $formats[0] = true;
                    $formats[1] = true;
                    $formats[2] = true;
                }
            }
        }
        $types = [];
        if (isset($formats[0])) {
            $types []= new Union([
                new TKeyedArray([
                    'r' => new Union([new TIntRange(0, 255)]),
                    'g' => new Union([new TIntRange(0, 255)]),
                    'b' => new Union([new TIntRange(0, 255)]),
                    'a' => new Union([new TIntRange(0, 1)])
                ])
            ]);
        }
        if (isset($formats[1])) {
            $types []= new Union([
                new TKeyedArray([
                    'r' => Type::getFloat(),
                    'g' => Type::getFloat(),
                    'b' => Type::getFloat(),
                    'a' => Type::getFloat()
                ])
            ]);
        }
        if (isset($formats[2])) {
            $types []= new Union([
                new TKeyedArray([
                    'r' => new Union([new TIntRange(0, 255)]),
                    'g' => new Union([new TIntRange(0, 255)]),
                    'b' => new Union([new TIntRange(0, 255)]),
                    'a' => new Union([new TIntRange(0, 255)])
                ])
            ]);
        }

        assert($types !== []);
        return Type::combineUnionTypeArray($types, $event->getSource()->getCodebase());
    }
}
