<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Union;

use function array_merge;
use function array_values;
use function count;
use function is_string;
use function max;
use function mb_strcut;

class ArrayMergeReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['array_merge', 'array_replace'];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): Union
    {
        $statements_source = $event->getStatementsSource();
        $call_args = $event->getCallArgs();
        if (!$statements_source instanceof StatementsAnalyzer
            || !$call_args
        ) {
            return Type::getMixed();
        }

        $is_replace = mb_strcut($event->getFunctionId(), 6, 7) === 'replace';

        $inner_value_types = [];
        $inner_key_types = [];

        $codebase = $statements_source->getCodebase();

        $generic_properties = [];
        $class_strings = [];
        $all_keyed_arrays = true;
        $all_int_offsets = true;
        $all_nonempty_lists = true;
        $any_nonempty = false;

        $max_keyed_array_size = 0;

        foreach ($call_args as $call_arg) {
            if (!($call_arg_type = $statements_source->node_data->getType($call_arg->value))) {
                return Type::getArray();
            }

            foreach ($call_arg_type->getAtomicTypes() as $type_part) {
                if ($call_arg->unpack) {
                    if (!$type_part instanceof TArray) {
                        if ($type_part instanceof TKeyedArray) {
                            $type_part_value_type = $type_part->getGenericValueType();
                        } elseif ($type_part instanceof TList) {
                            $type_part_value_type = $type_part->type_param;
                        } else {
                            return Type::getArray();
                        }
                    } else {
                        $type_part_value_type = $type_part->type_params[1];
                    }

                    $unpacked_type_parts = [];

                    foreach ($type_part_value_type->getAtomicTypes() as $value_type_part) {
                        $unpacked_type_parts[] = $value_type_part;
                    }
                } else {
                    $unpacked_type_parts = [$type_part];
                }

                foreach ($unpacked_type_parts as $unpacked_type_part) {
                    if (!$unpacked_type_part instanceof TArray) {
                        if (($unpacked_type_part instanceof TFalse
                                && $call_arg_type->ignore_falsable_issues)
                            || ($unpacked_type_part instanceof TNull
                                && $call_arg_type->ignore_nullable_issues)
                        ) {
                            continue;
                        }

                        if ($unpacked_type_part instanceof TKeyedArray) {
                            $max_keyed_array_size = max(
                                $max_keyed_array_size,
                                count($unpacked_type_part->properties)
                            );

                            foreach ($unpacked_type_part->properties as $key => $type) {
                                if (!is_string($key)) {
                                    if ($is_replace) {
                                        $generic_properties[$key] = $type;
                                    } else {
                                        $generic_properties[] = $type;
                                    }
                                    continue;
                                }

                                if (isset($unpacked_type_part->class_strings[$key])) {
                                    $class_strings[$key] = true;
                                }

                                if (!isset($generic_properties[$key]) || !$type->possibly_undefined) {
                                    $generic_properties[$key] = $type;
                                } else {
                                    $was_possibly_undefined = $generic_properties[$key]->possibly_undefined;

                                    $generic_properties[$key] = Type::combineUnionTypes(
                                        $generic_properties[$key],
                                        $type,
                                        $codebase
                                    );

                                    $generic_properties[$key]->possibly_undefined = $was_possibly_undefined;
                                }
                            }

                            if (!$unpacked_type_part->is_list) {
                                $all_nonempty_lists = false;
                            }

                            if ($unpacked_type_part->sealed) {
                                $any_nonempty = true;
                            }

                            continue;
                        }

                        if ($unpacked_type_part instanceof TList) {
                            $all_keyed_arrays = false;

                            if (!$unpacked_type_part instanceof TNonEmptyList) {
                                $all_nonempty_lists = false;
                            } else {
                                $any_nonempty = true;
                            }
                        } else {
                            if ($unpacked_type_part instanceof TMixed
                                && $unpacked_type_part->from_loop_isset
                            ) {
                                $unpacked_type_part = new TArray([
                                    Type::getArrayKey(),
                                    Type::getMixed(true),
                                ]);
                            } else {
                                return Type::getArray();
                            }
                        }
                    } else {
                        if (!$unpacked_type_part->type_params[0]->isEmpty()) {
                            foreach ($generic_properties as $key => $keyed_type) {
                                $generic_properties[$key] = Type::combineUnionTypes(
                                    $keyed_type,
                                    $unpacked_type_part->type_params[1],
                                    $codebase
                                );
                            }

                            $all_keyed_arrays = false;
                            $all_nonempty_lists = false;
                        }
                    }

                    if ($unpacked_type_part instanceof TArray) {
                        if ($unpacked_type_part->type_params[1]->isEmpty()) {
                            continue;
                        }

                        if (!$unpacked_type_part->type_params[0]->isInt()) {
                            $all_int_offsets = false;
                        }

                        if ($unpacked_type_part instanceof TNonEmptyArray) {
                            $any_nonempty = true;
                        }
                    }

                    $inner_key_types = array_merge(
                        $inner_key_types,
                        $unpacked_type_part instanceof TList
                            ? [new TInt()]
                            : array_values($unpacked_type_part->type_params[0]->getAtomicTypes())
                    );
                    $inner_value_types = array_merge(
                        $inner_value_types,
                        $unpacked_type_part instanceof TList
                            ? array_values($unpacked_type_part->type_param->getAtomicTypes())
                            : array_values($unpacked_type_part->type_params[1]->getAtomicTypes())
                    );
                }
            }
        }

        $inner_key_type = null;
        $inner_value_type = null;

        if ($inner_key_types) {
            /**
             * Truthy&array-shape-list doesn't reconcile correctly, will be fixed for 5.x by #8050.
             * @psalm-suppress InvalidScalarArgument
             */
            $inner_key_type = TypeCombiner::combine($inner_key_types, $codebase, true);
        }

        if ($inner_value_types) {
            $inner_value_type = TypeCombiner::combine($inner_value_types, $codebase, true);
        }

        $generic_property_count = count($generic_properties);

        if ($generic_properties
            && $generic_property_count < 64
            && ($generic_property_count < $max_keyed_array_size * 2
                || $generic_property_count < 16)
        ) {
            $objectlike = new TKeyedArray($generic_properties);

            if ($class_strings !== []) {
                $objectlike->class_strings = $class_strings;
            }

            if ($all_nonempty_lists || $all_int_offsets) {
                $objectlike->is_list = true;
            }

            if (!$all_keyed_arrays) {
                $objectlike->previous_key_type = $inner_key_type;
                $objectlike->previous_value_type = $inner_value_type;
            }

            return new Union([$objectlike]);
        }

        if ($inner_value_type) {
            if ($all_int_offsets) {
                if ($any_nonempty) {
                    return new Union([
                        new TNonEmptyList($inner_value_type),
                    ]);
                }

                return new Union([
                    new TList($inner_value_type),
                ]);
            }

            $inner_key_type = $inner_key_type ?? Type::getArrayKey();

            if ($any_nonempty) {
                return new Union([
                    new TNonEmptyArray([
                        $inner_key_type,
                        $inner_value_type,
                    ]),
                ]);
            }

            return new Union([
                new TArray([
                    $inner_key_type,
                    $inner_value_type,
                ]),
            ]);
        }

        return Type::getArray();
    }
}
