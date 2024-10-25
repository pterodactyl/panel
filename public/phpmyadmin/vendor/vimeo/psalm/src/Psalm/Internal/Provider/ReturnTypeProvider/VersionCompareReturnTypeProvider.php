<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

use function count;

class VersionCompareReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['version_compare'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        if (count($call_args) > 2) {
            $operator_type = $statements_source->node_data->getType($call_args[2]->value);

            if ($operator_type) {
                if (!$operator_type->hasMixed()) {
                    $acceptable_operator_type = new Union([
                        new TLiteralString('<'),
                        new TLiteralString('lt'),
                        new TLiteralString('<='),
                        new TLiteralString('le'),
                        new TLiteralString('>'),
                        new TLiteralString('gt'),
                        new TLiteralString('>='),
                        new TLiteralString('ge'),
                        new TLiteralString('=='),
                        new TLiteralString('='),
                        new TLiteralString('eq'),
                        new TLiteralString('!='),
                        new TLiteralString('<>'),
                        new TLiteralString('ne'),
                    ]);

                    $codebase = $statements_source->getCodebase();

                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $operator_type,
                        $acceptable_operator_type
                    )) {
                        return Type::getBool();
                    }
                }
            }

            return new Union([
                new TBool,
                new TNull,
            ]);
        }

        return new Union([
            new TLiteralInt(-1),
            new TLiteralInt(0),
            new TLiteralInt(1),
        ]);
    }
}
