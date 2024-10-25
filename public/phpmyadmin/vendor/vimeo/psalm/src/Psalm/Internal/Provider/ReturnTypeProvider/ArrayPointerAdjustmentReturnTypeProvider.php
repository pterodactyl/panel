<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_merge;
use function array_shift;
use function in_array;

class ArrayPointerAdjustmentReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * These functions are already handled by the CoreGenericFunctions stub
     */
    const IGNORE_FUNCTION_IDS_FOR_FALSE_RETURN_TYPE = [
        'reset',
        'end',
        'current',
    ];

    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['current', 'next', 'prev', 'reset', 'end'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $function_id = $event->getFunctionId();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        $first_arg = $call_args[0]->value ?? null;

        if (!$first_arg) {
            return Type::getMixed();
        }

        $first_arg_type = $statements_source->node_data->getType($first_arg);

        if (!$first_arg_type) {
            return Type::getMixed();
        }

        $atomic_types = $first_arg_type->getAtomicTypes();

        $value_type = null;
        $definitely_has_items = false;

        while ($atomic_type = array_shift($atomic_types)) {
            if ($atomic_type instanceof TTemplateParam) {
                $atomic_types = array_merge($atomic_types, $atomic_type->as->getAtomicTypes());
                continue;
            }

            if ($atomic_type instanceof TArray) {
                $value_type = clone $atomic_type->type_params[1];
                $definitely_has_items = $atomic_type instanceof TNonEmptyArray;
            } elseif ($atomic_type instanceof TList) {
                $value_type = clone $atomic_type->type_param;
                $definitely_has_items = $atomic_type instanceof TNonEmptyList;
            } elseif ($atomic_type instanceof TKeyedArray) {
                $value_type = $atomic_type->getGenericValueType();
                $definitely_has_items = $atomic_type->getGenericArrayType() instanceof TNonEmptyArray;
            } else {
                return Type::getMixed();
            }
        }

        if (!$value_type) {
            throw new UnexpectedValueException('This should never happen');
        }

        if ($value_type->isEmpty()) {
            $value_type = Type::getFalse();
        } elseif (!$definitely_has_items || self::isFunctionAlreadyHandledByStub($function_id)) {
            $value_type->addType(new TFalse);

            $codebase = $statements_source->getCodebase();

            if ($codebase->config->ignore_internal_falsable_issues) {
                $value_type->ignore_falsable_issues = true;
            }
        }

        ArrayFetchAnalyzer::taintArrayFetch(
            $statements_source,
            $first_arg,
            null,
            $value_type,
            Type::getMixed()
        );

        return $value_type;
    }

    private static function isFunctionAlreadyHandledByStub(string $function_id): bool
    {
        return !in_array($function_id, self::IGNORE_FUNCTION_IDS_FOR_FALSE_RETURN_TYPE, true);
    }
}
