<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Union;

use function count;
use function explode;
use function in_array;
use function reset;
use function strpos;
use function strtolower;
use function substr;

class ArrayReduceReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_reduce'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        $context = $event->getContext();
        if (!$statements_source instanceof StatementsAnalyzer) {
            return Type::getMixed();
        }

        if (!isset($call_args[0]) || !isset($call_args[1])) {
            return Type::getMixed();
        }

        $codebase = $statements_source->getCodebase();

        $array_arg = $call_args[0]->value;
        $function_call_arg = $call_args[1]->value;

        $array_arg_type = $statements_source->node_data->getType($array_arg);
        $function_call_arg_type = $statements_source->node_data->getType($function_call_arg);

        if (!$array_arg_type || !$function_call_arg_type) {
            return Type::getMixed();
        }

        $array_arg_types = $array_arg_type->getAtomicTypes();

        $array_arg_atomic_type = null;

        if (isset($array_arg_types['array'])
            && ($array_arg_types['array'] instanceof TArray
                || $array_arg_types['array'] instanceof TKeyedArray
                || $array_arg_types['array'] instanceof TList)
        ) {
            $array_arg_atomic_type = $array_arg_types['array'];

            if ($array_arg_atomic_type instanceof TKeyedArray) {
                $array_arg_atomic_type = $array_arg_atomic_type->getGenericArrayType();
            } elseif ($array_arg_atomic_type instanceof TList) {
                $array_arg_atomic_type = new TArray([
                    Type::getInt(),
                    clone $array_arg_atomic_type->type_param
                ]);
            }
        }

        if (!isset($call_args[2])) {
            $reduce_return_type = Type::getNull();
            $reduce_return_type->ignore_nullable_issues = true;
        } else {
            $reduce_return_type = $statements_source->node_data->getType($call_args[2]->value);

            if (!$reduce_return_type) {
                return Type::getMixed();
            }

            if ($reduce_return_type->hasMixed()) {
                return Type::getMixed();
            }
        }

        $initial_type = $reduce_return_type;

        $closure_types = $function_call_arg_type->getClosureTypes() ?: $function_call_arg_type->getCallableTypes();

        if ($closure_types) {
            $closure_atomic_type = reset($closure_types);

            $closure_return_type = $closure_atomic_type->return_type ?: Type::getMixed();

            if ($closure_return_type->isVoid()) {
                $closure_return_type = Type::getNull();
            }

            $reduce_return_type = Type::combineUnionTypes($closure_return_type, $reduce_return_type);

            if ($closure_atomic_type->params !== null) {
                if (count($closure_atomic_type->params) < 1) {
                    IssueBuffer::maybeAdd(
                        new InvalidArgument(
                            'The closure passed to array_reduce at least one parameter',
                            new CodeLocation($statements_source, $function_call_arg)
                        ),
                        $statements_source->getSuppressedIssues()
                    );

                    return Type::getMixed();
                }

                $carry_param = $closure_atomic_type->params[0];
                $item_param = $closure_atomic_type->params[1] ?? null;

                if ($carry_param->type
                    && (
                        !UnionTypeComparator::isContainedBy(
                            $codebase,
                            $initial_type,
                            $carry_param->type
                        )
                        || (
                            !$reduce_return_type->hasMixed()
                                && !UnionTypeComparator::isContainedBy(
                                    $codebase,
                                    $reduce_return_type,
                                    $carry_param->type
                                )
                            )
                        )
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidArgument(
                            'The first param of the closure passed to array_reduce must take '
                                . $reduce_return_type . ' but only accepts ' . $carry_param->type,
                            $carry_param->type_location
                                ?: new CodeLocation($statements_source, $function_call_arg)
                        ),
                        $statements_source->getSuppressedIssues()
                    );

                    return Type::getMixed();
                }

                if ($item_param
                    && $item_param->type
                    && $array_arg_atomic_type
                    && !$array_arg_atomic_type->type_params[1]->hasMixed()
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $array_arg_atomic_type->type_params[1],
                        $item_param->type
                    )
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidArgument(
                            'The second param of the closure passed to array_reduce must take '
                                . $array_arg_atomic_type->type_params[1] . ' but only accepts ' . $item_param->type,
                            $item_param->type_location
                                ?: new CodeLocation($statements_source, $function_call_arg)
                        ),
                        $statements_source->getSuppressedIssues()
                    );

                    return Type::getMixed();
                }
            }

            return $reduce_return_type;
        }

        if ($function_call_arg instanceof PhpParser\Node\Scalar\String_
            || $function_call_arg instanceof PhpParser\Node\Expr\Array_
            || $function_call_arg instanceof PhpParser\Node\Expr\BinaryOp\Concat
        ) {
            $mapping_function_ids = CallAnalyzer::getFunctionIdsFromCallableArg(
                $statements_source,
                $function_call_arg
            );

            $call_map = InternalCallMapHandler::getCallMap();

            foreach ($mapping_function_ids as $mapping_function_id) {
                $mapping_function_id_parts = explode('&', $mapping_function_id);

                $part_match_found = false;

                foreach ($mapping_function_id_parts as $mapping_function_id_part) {
                    if (isset($call_map[$mapping_function_id_part][0])) {
                        if ($call_map[$mapping_function_id_part][0]) {
                            $mapped_function_return =
                                Type::parseString($call_map[$mapping_function_id_part][0]);

                            $reduce_return_type = Type::combineUnionTypes(
                                $reduce_return_type,
                                $mapped_function_return
                            );

                            $part_match_found = true;
                        }
                    } elseif ($mapping_function_id_part) {
                        if (strpos($mapping_function_id_part, '::') !== false) {
                            if ($mapping_function_id_part[0] === '$') {
                                $mapping_function_id_part = substr($mapping_function_id_part, 1);
                            }

                            [$callable_fq_class_name, $method_name] = explode('::', $mapping_function_id_part);

                            if (in_array($callable_fq_class_name, ['self', 'static'], true)) {
                                $callable_fq_class_name = $statements_source->getFQCLN();
                                if ($callable_fq_class_name === null) {
                                    continue;
                                }
                            }

                            if ($callable_fq_class_name === 'parent') {
                                continue;
                            }

                            $method_id = new MethodIdentifier(
                                $callable_fq_class_name,
                                strtolower($method_name)
                            );

                            if (!$codebase->methods->methodExists(
                                $method_id,
                                !$context->collect_initializations
                                    && !$context->collect_mutations
                                    ? $context->calling_method_id
                                    : null,
                                $codebase->collect_locations
                                    ? new CodeLocation(
                                        $statements_source,
                                        $function_call_arg
                                    ) : null,
                                null,
                                $statements_source->getFilePath()
                            )) {
                                continue;
                            }

                            $part_match_found = true;

                            $self_class = 'self';

                            $return_type = $codebase->methods->getMethodReturnType(
                                $method_id,
                                $self_class
                            ) ?? Type::getMixed();
                        } else {
                            if (!$codebase->functions->functionExists(
                                $statements_source,
                                strtolower($mapping_function_id_part)
                            )
                            ) {
                                return Type::getMixed();
                            }

                            $part_match_found = true;

                            $function_storage = $codebase->functions->getStorage(
                                $statements_source,
                                strtolower($mapping_function_id_part)
                            );

                            $return_type = $function_storage->return_type ?: Type::getMixed();
                        }

                        $reduce_return_type = Type::combineUnionTypes($reduce_return_type, $return_type);
                    }
                }

                if ($part_match_found === false) {
                    return Type::getMixed();
                }
            }

            return $reduce_return_type;
        }

        return Type::getMixed();
    }
}
