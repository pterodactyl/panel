<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_merge;
use function array_shift;
use function assert;

class IteratorToArrayReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [
            'iterator_to_array',
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        $context = $event->getContext();
        if (!$statements_source instanceof StatementsAnalyzer
            || !$call_args
        ) {
            return Type::getMixed();
        }

        if (($first_arg_type = $statements_source->node_data->getType($call_args[0]->value))) {
            $key_type = null;
            $value_type = null;

            $codebase = $statements_source->getCodebase();

            $atomic_types = $first_arg_type->getAtomicTypes();

            while ($call_arg_atomic_type = array_shift($atomic_types)) {
                if ($call_arg_atomic_type instanceof TTemplateParam) {
                    $atomic_types = array_merge($atomic_types, $call_arg_atomic_type->as->getAtomicTypes());
                    continue;
                }

                if ($call_arg_atomic_type instanceof TNamedObject
                    && AtomicTypeComparator::isContainedBy(
                        $codebase,
                        $call_arg_atomic_type,
                        new TIterable([Type::getMixed(), Type::getMixed()])
                    )
                ) {
                    $has_valid_iterator = true;
                    ForeachAnalyzer::handleIterable(
                        $statements_source,
                        $call_arg_atomic_type,
                        $call_args[0]->value,
                        $codebase,
                        $context,
                        $key_type,
                        $value_type,
                        $has_valid_iterator
                    );
                }
            }

            if ($value_type) {
                $second_arg_type = isset($call_args[1])
                    ? $statements_source->node_data->getType($call_args[1]->value)
                    : null;

                if ($second_arg_type
                    && ((string) $second_arg_type === 'false')
                ) {
                    return new Union([
                        new TList($value_type),
                    ]);
                }

                $key_type = $key_type
                    && (!isset($call_args[1])
                        || ($second_arg_type && ((string) $second_arg_type === 'true')))
                    ? $key_type
                    : Type::getArrayKey();

                if ($key_type->hasMixed()) {
                    $key_type = Type::getArrayKey();
                }

                if ($key_type->isSingle() && $key_type->hasTemplate()) {
                    $template_types = $key_type->getTemplateTypes();
                    $template_type = array_shift($template_types);
                    if ($template_type->as->hasMixed()) {
                        $template_type->as = Type::getArrayKey();
                        $key_type = new Union([$template_type]);
                    }
                }

                return new Union([
                    new TArray([
                        $key_type,
                        $value_type,
                    ]),
                ]);
            }
        }

        $callmap_callables = InternalCallMapHandler::getCallablesFromCallMap($function_id);

        assert($callmap_callables && $callmap_callables[0]->return_type);

        return $callmap_callables[0]->return_type;
    }
}
