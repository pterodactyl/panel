<?php

namespace Psalm\Internal\Type;

use InvalidArgumentException;
use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableArray;
use Psalm\Type\Atomic\TCallableKeyedArray;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TCallableString;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TEmptyMixed;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TLowercaseString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNonspecificLiteralInt;
use Psalm\Type\Atomic\TNonspecificLiteralString;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TPositiveInt;
use Psalm\Type\Atomic\TScalar;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_filter;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_values;
use function count;
use function get_class;
use function is_int;
use function is_numeric;
use function strpos;
use function strtolower;
use function substr;

class TypeCombiner
{
    /**
     * Combines types together
     *  - so `int + string = int|string`
     *  - so `array<int> + array<string> = array<int|string>`
     *  - and `array<int> + string = array<int>|string`
     *  - and `array<empty> + array<empty> = array<empty>`
     *  - and `array<string> + array<empty> = array<string>`
     *  - and `array + array<string> = array<mixed>`
     *
     * @param  non-empty-list<Atomic>    $types
     * @param  int    $literal_limit any greater number of literal types than this
     *                               will be merged to a scalar
     *
     */
    public static function combine(
        array $types,
        ?Codebase $codebase = null,
        bool $overwrite_empty_array = false,
        bool $allow_mixed_union = true,
        int $literal_limit = 500
    ): Union {
        if (count($types) === 1) {
            $union_type = new Union([$types[0]]);

            if ($types[0]->from_docblock) {
                $union_type->from_docblock = true;
            }

            return $union_type;
        }

        $combination = new TypeCombination();

        $from_docblock = false;

        foreach ($types as $type) {
            $from_docblock = $from_docblock || $type->from_docblock;

            $result = self::scrapeTypeProperties(
                $type,
                $combination,
                $codebase,
                $overwrite_empty_array,
                $allow_mixed_union,
                $literal_limit
            );

            if ($result) {
                if ($from_docblock) {
                    $result->from_docblock = true;
                }

                return $result;
            }
        }

        if (count($combination->value_types) === 1
            && !count($combination->objectlike_entries)
            && !$combination->array_type_params
            && !$combination->builtin_type_params
            && !$combination->object_type_params
            && !$combination->named_object_types
            && !$combination->strings
            && !$combination->class_string_types
            && !$combination->ints
            && !$combination->floats
        ) {
            if (isset($combination->value_types['false'])) {
                $union_type = Type::getFalse();

                if ($from_docblock) {
                    $union_type->from_docblock = true;
                }

                return $union_type;
            }

            if (isset($combination->value_types['true'])) {
                $union_type = Type::getTrue();

                if ($from_docblock) {
                    $union_type->from_docblock = true;
                }

                return $union_type;
            }
        } elseif (isset($combination->value_types['void'])) {
            unset($combination->value_types['void']);

            // if we're merging with another type, we cannot represent it in PHP
            $from_docblock = true;

            if (!isset($combination->value_types['null'])) {
                $combination->value_types['null'] = new TNull();
            }
        }

        if (isset($combination->value_types['true']) && isset($combination->value_types['false'])) {
            unset($combination->value_types['true'], $combination->value_types['false']);

            $combination->value_types['bool'] = new TBool();
        }

        if ($combination->array_type_params
            && (isset($combination->named_object_types['Traversable'])
                || isset($combination->builtin_type_params['Traversable']))
            && (
                ($codebase && $codebase->config->allow_phpstorm_generics)
                || isset($combination->builtin_type_params['Traversable'])
                || (isset($combination->named_object_types['Traversable'])
                    && $combination->named_object_types['Traversable']->from_docblock)
            )
            && !$combination->extra_types
        ) {
            $array_param_types = $combination->array_type_params;
            $traversable_param_types = $combination->builtin_type_params['Traversable']
                ?? [Type::getMixed(), Type::getMixed()];

            $combined_param_types = [];

            foreach ($array_param_types as $i => $array_param_type) {
                $combined_param_types[] = Type::combineUnionTypes($array_param_type, $traversable_param_types[$i]);
            }

            $combination->value_types['iterable'] = new TIterable($combined_param_types);

            $combination->array_type_params = [];

            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            unset(
                $combination->value_types['array'],
                $combination->named_object_types['Traversable'],
                $combination->builtin_type_params['Traversable']
            );
        }

        if ($combination->empty_mixed && $combination->non_empty_mixed) {
            $combination->value_types['mixed'] = new TMixed((bool) $combination->mixed_from_loop_isset);
        }

        $new_types = [];

        if ($combination->objectlike_entries) {
            $new_types = self::handleKeyedArrayEntries(
                $combination,
                $overwrite_empty_array
            );
        }

        if ($combination->array_type_params) {
            if (count($combination->array_type_params) !== 2) {
                throw new UnexpectedValueException('Unexpected number of parameters');
            }

            $new_types[] = self::getArrayTypeFromGenericParams(
                $codebase,
                $combination,
                $overwrite_empty_array,
                $allow_mixed_union,
                $type,
                $combination->array_type_params
            );
        }

        if ($combination->extra_types) {
            /** @psalm-suppress PropertyTypeCoercion */
            $combination->extra_types = self::combine(
                array_values($combination->extra_types),
                $codebase
            )->getAtomicTypes();
        }

        foreach ($combination->builtin_type_params as $generic_type => $generic_type_params) {
            if ($generic_type === 'iterable') {
                $new_types[] = new TIterable($generic_type_params);
            } else {
                $generic_object = new TGenericObject($generic_type, $generic_type_params);

                /** @psalm-suppress PropertyTypeCoercion */
                $generic_object->extra_types = $combination->extra_types;
                $new_types[] = $generic_object;

                if ($combination->named_object_types) {
                    unset($combination->named_object_types[$generic_type]);
                }
            }
        }

        foreach ($combination->object_type_params as $generic_type => $generic_type_params) {
            $generic_type = substr($generic_type, 0, (int) strpos($generic_type, '<'));

            $generic_object = new TGenericObject($generic_type, $generic_type_params);

            if ($combination->object_static[$generic_type] ?? false) {
                $generic_object->was_static = true;
            }

            /** @psalm-suppress PropertyTypeCoercion */
            $generic_object->extra_types = $combination->extra_types;
            $new_types[] = $generic_object;
        }

        if ($combination->class_string_types) {
            if ($combination->strings) {
                foreach ($combination->strings as $k => $string) {
                    if ($string instanceof TLiteralClassString) {
                        $combination->class_string_types[$string->value] = new TNamedObject($string->value);
                        unset($combination->strings[$k]);
                    }
                }
            }

            $has_non_specific_string = isset($combination->value_types['string'])
                && get_class($combination->value_types['string']) === TString::class;

            if (!$has_non_specific_string) {
                $object_type = self::combine(
                    array_values($combination->class_string_types),
                    $codebase
                );

                foreach ($object_type->getAtomicTypes() as $object_atomic_type) {
                    if ($object_atomic_type instanceof TNamedObject) {
                        $new_types[] = new TClassString($object_atomic_type->value, $object_atomic_type);
                    } elseif ($object_atomic_type instanceof TObject) {
                        $new_types[] = new TClassString();
                    }
                }
            }
        }

        if ($combination->strings) {
            $new_types = array_merge($new_types, array_values($combination->strings));
        }

        if ($combination->ints) {
            $new_types = array_merge($new_types, array_values($combination->ints));
        }

        if ($combination->floats) {
            $new_types = array_merge($new_types, array_values($combination->floats));
        }

        if (isset($combination->value_types['string'])
            && isset($combination->value_types['int'])
            && isset($combination->value_types['bool'])
            && isset($combination->value_types['float'])
        ) {
            unset(
                $combination->value_types['string'],
                $combination->value_types['int'],
                $combination->value_types['bool'],
                $combination->value_types['float']
            );
            $combination->value_types['scalar'] = new TScalar;
        }

        if ($combination->named_object_types !== null) {
            $combination->value_types += $combination->named_object_types;
        }

        $has_empty = (int) isset($combination->value_types['empty']);
        $has_never = false;

        foreach ($combination->value_types as $type) {
            if ($type instanceof TMixed
                && $combination->mixed_from_loop_isset
                && (count($combination->value_types) > (1 + $has_empty) || count($new_types) > $has_empty)
            ) {
                continue;
            }

            if (($type instanceof TEmpty || $type instanceof TNever)
                && (count($combination->value_types) > 1 || count($new_types))
            ) {
                $has_never = true;
                continue;
            }

            $new_types[] = $type;
        }

        if (!$new_types && !$has_never) {
            throw new UnexpectedValueException('There should be types here');
        } elseif (!$new_types && $has_never) {
            $union_type = Type::getNever();
        } else {
            $union_type = new Union($new_types);
        }

        if ($from_docblock) {
            $union_type->from_docblock = true;
        }

        return $union_type;
    }

    private static function scrapeTypeProperties(
        Atomic $type,
        TypeCombination $combination,
        ?Codebase $codebase,
        bool $overwrite_empty_array,
        bool $allow_mixed_union,
        int $literal_limit
    ): ?Union {
        if ($type instanceof TMixed) {
            if ($type->from_loop_isset) {
                if ($combination->mixed_from_loop_isset === null) {
                    $combination->mixed_from_loop_isset = true;
                } else {
                    return null;
                }
            } else {
                $combination->mixed_from_loop_isset = false;
            }

            if ($type instanceof TNonEmptyMixed) {
                $combination->non_empty_mixed = true;

                if ($combination->empty_mixed) {
                    return null;
                }
            } elseif ($type instanceof TEmptyMixed) {
                $combination->empty_mixed = true;

                if ($combination->non_empty_mixed) {
                    return null;
                }
            } else {
                $combination->empty_mixed = true;
                $combination->non_empty_mixed = true;
            }

            if (!$allow_mixed_union) {
                return Type::getMixed($combination->mixed_from_loop_isset);
            }
        }

        // deal with false|bool => bool
        if (($type instanceof TFalse || $type instanceof TTrue) && isset($combination->value_types['bool'])) {
            return null;
        }

        if (get_class($type) === TBool::class && isset($combination->value_types['false'])) {
            unset($combination->value_types['false']);
        }

        if (get_class($type) === TBool::class && isset($combination->value_types['true'])) {
            unset($combination->value_types['true']);
        }

        if ($type instanceof TArray && isset($combination->builtin_type_params['iterable'])) {
            $type_key = 'iterable';
        } elseif ($type instanceof TArray
            && $type->type_params[1]->isMixed()
            && isset($combination->value_types['iterable'])
        ) {
            $type_key = 'iterable';
            $combination->builtin_type_params['iterable'] = [Type::getMixed(), Type::getMixed()];
        } elseif ($type instanceof TNamedObject
            && $type->value === 'Traversable'
            && (isset($combination->builtin_type_params['iterable']) || isset($combination->value_types['iterable']))
        ) {
            $type_key = 'iterable';

            if (!isset($combination->builtin_type_params['iterable'])) {
                $combination->builtin_type_params['iterable'] = [Type::getMixed(), Type::getMixed()];
            }

            if (!$type instanceof TGenericObject) {
                $type = new TGenericObject($type->value, [Type::getMixed(), Type::getMixed()]);
            }
        } elseif ($type instanceof TNamedObject && ($type->value === 'Traversable' || $type->value === 'Generator')) {
            $type_key = $type->value;
        } else {
            $type_key = $type->getKey();
        }

        if ($type instanceof TIterable
            && $combination->array_type_params
            && ($type->has_docblock_params || $combination->array_type_params[1]->isMixed())
        ) {
            if (!isset($combination->builtin_type_params['iterable'])) {
                $combination->builtin_type_params['iterable'] = $combination->array_type_params;
            } else {
                foreach ($combination->array_type_params as $i => $array_type_param) {
                    $iterable_type_param = $combination->builtin_type_params['iterable'][$i];
                    /** @psalm-suppress PropertyTypeCoercion */
                    $combination->builtin_type_params['iterable'][$i] = Type::combineUnionTypes(
                        $iterable_type_param,
                        $array_type_param
                    );
                }
            }

            $combination->array_type_params = [];
        }

        if ($type instanceof TIterable
            && (isset($combination->named_object_types['Traversable'])
                || isset($combination->builtin_type_params['Traversable']))
        ) {
            if (!isset($combination->builtin_type_params['iterable'])) {
                $combination->builtin_type_params['iterable']
                    = $combination->builtin_type_params['Traversable'] ?? [Type::getMixed(), Type::getMixed()];
            } elseif (isset($combination->builtin_type_params['Traversable'])) {
                foreach ($combination->builtin_type_params['Traversable'] as $i => $array_type_param) {
                    $iterable_type_param = $combination->builtin_type_params['iterable'][$i];
                    /** @psalm-suppress PropertyTypeCoercion */
                    $combination->builtin_type_params['iterable'][$i] = Type::combineUnionTypes(
                        $iterable_type_param,
                        $array_type_param
                    );
                }
            } else {
                $combination->builtin_type_params['iterable'] = [Type::getMixed(), Type::getMixed()];
            }

            /** @psalm-suppress PossiblyNullArrayAccess */
            unset(
                $combination->named_object_types['Traversable'],
                $combination->builtin_type_params['Traversable']
            );
        }

        if ($type instanceof TNamedObject
            || $type instanceof TTemplateParam
            || $type instanceof TIterable
            || $type instanceof TObjectWithProperties
        ) {
            if ($type->extra_types) {
                $combination->extra_types = array_merge(
                    $combination->extra_types ?: [],
                    $type->extra_types
                );
            }
        }

        if ($type instanceof TNamedObject) {
            if (array_key_exists($type->value, $combination->object_static)) {
                if ($combination->object_static[$type->value] && !$type->was_static) {
                    $combination->object_static[$type->value] = false;
                }
            } else {
                $combination->object_static[$type->value] = $type->was_static;
            }
        }

        if ($type instanceof TArray && $type_key === 'array') {
            if ($type instanceof TCallableArray && isset($combination->value_types['callable'])) {
                return null;
            }

            foreach ($type->type_params as $i => $type_param) {
                /** @psalm-suppress PropertyTypeCoercion */
                $combination->array_type_params[$i] = Type::combineUnionTypes(
                    $combination->array_type_params[$i] ?? null,
                    $type_param,
                    $codebase,
                    $overwrite_empty_array
                );
            }

            if ($type instanceof TNonEmptyArray) {
                if ($combination->array_counts !== null) {
                    if ($type->count === null) {
                        $combination->array_counts = null;
                    } else {
                        $combination->array_counts[$type->count] = true;
                    }
                }

                $combination->array_sometimes_filled = true;
            } else {
                $combination->array_always_filled = false;
            }

            if (!$type->type_params[1]->isEmpty()) {
                $combination->all_arrays_lists = false;
                $combination->all_arrays_class_string_maps = false;
            }

            if ($type instanceof TCallableArray) {
                if ($combination->all_arrays_callable !== false) {
                    $combination->all_arrays_callable = true;
                }
            } else {
                $combination->all_arrays_callable = false;
            }

            return null;
        }

        if ($type instanceof TList) {
            foreach ([Type::getInt(), $type->type_param] as $i => $type_param) {
                /** @psalm-suppress PropertyTypeCoercion */
                $combination->array_type_params[$i] = Type::combineUnionTypes(
                    $combination->array_type_params[$i] ?? null,
                    $type_param,
                    $codebase,
                    $overwrite_empty_array
                );
            }

            if ($type instanceof TNonEmptyList) {
                if ($combination->array_counts !== null) {
                    if ($type->count === null) {
                        $combination->array_counts = null;
                    } else {
                        $combination->array_counts[$type->count] = true;
                    }
                }

                $combination->array_sometimes_filled = true;
            } else {
                $combination->array_always_filled = false;
            }

            if ($combination->all_arrays_lists !== false) {
                $combination->all_arrays_lists = true;
            }

            $combination->all_arrays_callable = false;
            $combination->all_arrays_class_string_maps = false;

            return null;
        }

        if ($type instanceof TClassStringMap) {
            foreach ([$type->getStandinKeyParam(), $type->value_param] as $i => $type_param) {
                /** @psalm-suppress PropertyTypeCoercion */
                $combination->array_type_params[$i] = Type::combineUnionTypes(
                    $combination->array_type_params[$i] ?? null,
                    $type_param,
                    $codebase,
                    $overwrite_empty_array
                );
            }

            $combination->array_always_filled = false;

            if ($combination->all_arrays_class_string_maps !== false) {
                $combination->all_arrays_class_string_maps = true;
                $combination->class_string_map_names[$type->param_name] = true;
                $combination->class_string_map_as_types[(string) $type->as_type] = $type->as_type;
            }

            return null;
        }

        if (($type instanceof TGenericObject && ($type->value === 'Traversable' || $type->value === 'Generator'))
            || ($type instanceof TIterable && $type->has_docblock_params)
            || ($type instanceof TArray && $type_key === 'iterable')
        ) {
            foreach ($type->type_params as $i => $type_param) {
                /** @psalm-suppress PropertyTypeCoercion */
                $combination->builtin_type_params[$type_key][$i] = Type::combineUnionTypes(
                    $combination->builtin_type_params[$type_key][$i] ?? null,
                    $type_param,
                    $codebase,
                    $overwrite_empty_array
                );
            }

            return null;
        }

        if ($type instanceof TGenericObject) {
            foreach ($type->type_params as $i => $type_param) {
                /** @psalm-suppress PropertyTypeCoercion */
                $combination->object_type_params[$type_key][$i] = Type::combineUnionTypes(
                    $combination->object_type_params[$type_key][$i] ?? null,
                    $type_param,
                    $codebase,
                    $overwrite_empty_array
                );
            }

            return null;
        }

        if ($type instanceof TKeyedArray) {
            if ($type instanceof TCallableKeyedArray && isset($combination->value_types['callable'])) {
                return null;
            }

            $existing_objectlike_entries = (bool) $combination->objectlike_entries;
            $possibly_undefined_entries = $combination->objectlike_entries;
            $combination->objectlike_sealed = $combination->objectlike_sealed && $type->sealed;

            if ($type->previous_value_type) {
                $combination->objectlike_value_type = Type::combineUnionTypes(
                    $type->previous_value_type,
                    $combination->objectlike_value_type,
                    $codebase,
                    $overwrite_empty_array
                );
            }

            if ($type->previous_key_type) {
                $combination->objectlike_key_type = Type::combineUnionTypes(
                    $type->previous_key_type,
                    $combination->objectlike_key_type,
                    $codebase,
                    $overwrite_empty_array
                );
            }

            $has_defined_keys = false;

            foreach ($type->properties as $candidate_property_name => $candidate_property_type) {
                $value_type = $combination->objectlike_entries[$candidate_property_name] ?? null;

                if (!$value_type) {
                    $combination->objectlike_entries[$candidate_property_name] = clone $candidate_property_type;
                    // it's possibly undefined if there are existing objectlike entries and
                    $combination->objectlike_entries[$candidate_property_name]->possibly_undefined
                        = $existing_objectlike_entries || $candidate_property_type->possibly_undefined;
                } else {
                    $combination->objectlike_entries[$candidate_property_name] = Type::combineUnionTypes(
                        $value_type,
                        $candidate_property_type,
                        $codebase,
                        $overwrite_empty_array
                    );
                }

                if (!$type->previous_value_type) {
                    unset($possibly_undefined_entries[$candidate_property_name]);
                }

                if (!$candidate_property_type->possibly_undefined) {
                    $has_defined_keys = true;
                }
            }

            if (!$has_defined_keys) {
                $combination->array_always_filled = false;
            }

            if ($combination->array_counts !== null) {
                $combination->array_counts[count($type->properties)] = true;
            }

            foreach ($possibly_undefined_entries as $possibly_undefined_type) {
                $possibly_undefined_type->possibly_undefined = true;
            }

            if (!$type->is_list) {
                $combination->all_arrays_lists = false;
            } elseif ($combination->all_arrays_lists !== false) {
                $combination->all_arrays_lists = true;
            }

            if ($type instanceof TCallableKeyedArray) {
                if ($combination->all_arrays_callable !== false) {
                    $combination->all_arrays_callable = true;
                }
            } else {
                $combination->all_arrays_callable = false;
            }

            $combination->all_arrays_class_string_maps = false;

            return null;
        }

        if ($type instanceof TObject) {
            if ($type instanceof TCallableObject && isset($combination->value_types['callable'])) {
                return null;
            }

            $combination->named_object_types = null;
            $combination->value_types[$type_key] = $type;

            return null;
        }

        if ($type instanceof TIterable) {
            $combination->value_types[$type_key] = $type;

            return null;
        }

        if ($type instanceof TNamedObject) {
            if ($combination->named_object_types === null) {
                return null;
            }

            if (isset($combination->named_object_types[$type_key])) {
                return null;
            }

            if (!$codebase) {
                $combination->named_object_types[$type_key] = $type;

                return null;
            }

            if (!$codebase->classlikes->classOrInterfaceOrEnumExists($type_key)) {
                // write this to the main list
                $combination->value_types[$type_key] = $type;

                return null;
            }

            $is_class = $codebase->classExists($type_key);

            foreach ($combination->named_object_types as $key => $_) {
                if ($codebase->classExists($key)) {
                    if ($codebase->classExtendsOrImplements($key, $type_key)) {
                        unset($combination->named_object_types[$key]);
                        continue;
                    }

                    if ($is_class) {
                        if ($codebase->classExtends($type_key, $key)) {
                            return null;
                        }
                    }
                } else {
                    if ($codebase->interfaceExtends($key, $type_key)) {
                        unset($combination->named_object_types[$key]);
                        continue;
                    }

                    if ($is_class) {
                        if ($codebase->classImplements($type_key, $key)) {
                            return null;
                        }
                    } else {
                        if ($codebase->interfaceExtends($type_key, $key)) {
                            return null;
                        }
                    }
                }
            }

            $combination->named_object_types[$type_key] = $type;

            return null;
        }

        if ($type instanceof TScalar) {
            $combination->strings = null;
            $combination->ints = null;
            $combination->floats = null;
            unset(
                $combination->value_types['string'],
                $combination->value_types['int'],
                $combination->value_types['bool'],
                $combination->value_types['true'],
                $combination->value_types['false'],
                $combination->value_types['float']
            );

            if (!isset($combination->value_types[$type_key])
                || $combination->value_types[$type_key]->getId() === $type->getId()
            ) {
                $combination->value_types[$type_key] = $type;
            } else {
                $combination->value_types[$type_key] = new TScalar();
            }

            return null;
        }

        if ($type instanceof Scalar && isset($combination->value_types['scalar'])) {
            return null;
        }

        if ($type instanceof TArrayKey) {
            $combination->strings = null;
            $combination->ints = null;
            unset(
                $combination->value_types['string'],
                $combination->value_types['int']
            );
            $combination->value_types[$type_key] = $type;

            return null;
        }

        if ($type instanceof TString) {
            self::scrapeStringProperties(
                $type_key,
                $type,
                $combination,
                $codebase,
                $literal_limit
            );

            return null;
        }

        if ($type instanceof TInt) {
            self::scrapeIntProperties(
                $type_key,
                $type,
                $combination,
                $literal_limit
            );

            return null;
        }

        if ($type instanceof TFloat) {
            if ($type instanceof TLiteralFloat) {
                if ($combination->floats !== null && count($combination->floats) < $literal_limit) {
                    $combination->floats[$type_key] = $type;
                } else {
                    $combination->floats = null;
                    $combination->value_types['float'] = new TFloat();
                }
            } else {
                $combination->floats = null;
                $combination->value_types['float'] = $type;
            }

            return null;
        }

        if ($type instanceof TCallable && $type_key === 'callable') {
            if (($combination->value_types['string'] ?? null) instanceof TCallableString) {
                unset($combination->value_types['string']);
            } elseif (!empty($combination->array_type_params) && $combination->all_arrays_callable) {
                $combination->array_type_params = [];
            } elseif (isset($combination->value_types['callable-object'])) {
                unset($combination->value_types['callable-object']);
            }
        }

        $combination->value_types[$type_key] = $type;
        return null;
    }

    private static function scrapeStringProperties(
        string $type_key,
        Atomic $type,
        TypeCombination $combination,
        ?Codebase $codebase,
        int $literal_limit
    ): void {
        if ($type instanceof TCallableString && isset($combination->value_types['callable'])) {
            return;
        }

        if (isset($combination->value_types['array-key'])) {
            return;
        }

        if ($type instanceof TTemplateParamClass) {
            $combination->value_types[$type_key] = $type;
        } elseif ($type instanceof TClassString) {
            if (!$type->as_type) {
                $combination->class_string_types['object'] = new TObject();
            } else {
                $combination->class_string_types[$type->as] = $type->as_type;
            }
        } elseif ($type instanceof TLiteralString) {
            if ($combination->strings !== null && count($combination->strings) < $literal_limit) {
                $combination->strings[$type_key] = $type;
            } else {
                $shared_classlikes = $codebase ? self::getSharedTypes($combination, $codebase) : [];

                $combination->strings = null;

                if (isset($combination->value_types['string'])
                    && $combination->value_types['string'] instanceof TNumericString
                    && is_numeric($type->value)
                ) {
                    // do nothing
                } elseif (isset($combination->value_types['class-string'])
                    && $type instanceof TLiteralClassString
                ) {
                    // do nothing
                } elseif ($type instanceof TLiteralClassString) {
                    $type_classlikes = $codebase
                        ? self::getClassLikes($codebase, $type->value)
                        : [];

                    $mutual = array_intersect_key($type_classlikes, $shared_classlikes);

                    if ($mutual) {
                        $first_class = array_keys($mutual)[0];

                        $combination->class_string_types[$first_class] = new TNamedObject($first_class);
                    } else {
                        $combination->class_string_types['object'] = new TObject();
                    }
                } elseif (isset($combination->value_types['string'])
                    && $combination->value_types['string'] instanceof TNonspecificLiteralString
                ) {
                    // do nothing
                } elseif (isset($combination->value_types['string'])
                    && $combination->value_types['string'] instanceof TLowercaseString
                    && strtolower($type->value) === $type->value
                ) {
                    // do nothing
                } elseif (isset($combination->value_types['string'])
                    && $combination->value_types['string'] instanceof TNonFalsyString
                    && $type->value
                ) {
                    // do nothing
                } elseif (isset($combination->value_types['string'])
                    && $combination->value_types['string'] instanceof TNonEmptyString
                    && $type->value !== ''
                ) {
                    // do nothing
                } else {
                    $combination->value_types['string'] = new TString();
                }
            }
        } else {
            $type_key = 'string';

            if (!isset($combination->value_types['string'])) {
                if ($combination->strings) {
                    if ($type instanceof TNumericString) {
                        $has_non_numeric_string = false;

                        foreach ($combination->strings as $string_type) {
                            if (!is_numeric($string_type->value)) {
                                $has_non_numeric_string = true;
                                break;
                            }
                        }

                        if ($has_non_numeric_string) {
                            $combination->value_types['string'] = new TString();
                        } else {
                            $combination->value_types['string'] = $type;
                        }
                    } elseif ($type instanceof TLowercaseString) {
                        $has_non_lowercase_string = false;

                        foreach ($combination->strings as $string_type) {
                            if (strtolower($string_type->value) !== $string_type->value) {
                                $has_non_lowercase_string = true;
                                break;
                            }
                        }

                        if ($has_non_lowercase_string) {
                            $combination->value_types['string'] = new TString();
                        } else {
                            $combination->value_types['string'] = $type;
                        }
                    } elseif ($type instanceof TNonEmptyString) {
                        $has_empty_string = false;

                        foreach ($combination->strings as $string_type) {
                            if (!$string_type->value) {
                                $has_empty_string = true;
                                break;
                            }
                        }

                        if ($has_empty_string) {
                            $combination->value_types['string'] = new TString();
                        } else {
                            $combination->value_types['string'] = $type;
                        }
                    } elseif ($type instanceof TNonspecificLiteralString) {
                        $combination->value_types['string'] = $type;
                    } else {
                        $combination->value_types[$type_key] = new TString();
                    }
                } else {
                    $combination->value_types[$type_key] = $type;
                }
            } elseif (get_class($combination->value_types['string']) !== TString::class) {
                if (get_class($type) === TString::class) {
                    $combination->value_types['string'] = $type;
                } elseif (get_class($combination->value_types['string']) !== get_class($type)) {
                    if (get_class($type) === TNonEmptyString::class
                        && get_class($combination->value_types['string']) === TNumericString::class
                    ) {
                        $combination->value_types['string'] = $type;
                    } elseif (get_class($type) === TNumericString::class
                        && get_class($combination->value_types['string']) === TNonEmptyString::class
                    ) {
                        // do nothing
                    } elseif ((get_class($type) === TNonEmptyString::class
                            || get_class($type) === TNumericString::class)
                        && get_class($combination->value_types['string']) === TNonFalsyString::class
                    ) {
                        $combination->value_types['string'] = $type;
                    } elseif (get_class($type) === TNonFalsyString::class
                        && (get_class($combination->value_types['string']) === TNonEmptyString::class
                            || get_class($combination->value_types['string']) === TNumericString::class)
                    ) {
                        // do nothing
                    } elseif ((get_class($type) === TNonEmptyString::class
                            || get_class($type) === TNonFalsyString::class)
                        && get_class($combination->value_types['string']) === TNonEmptyLowercaseString::class
                    ) {
                        $combination->value_types['string'] = new TNonEmptyString();
                    } elseif ((get_class($combination->value_types['string']) === TNonEmptyString::class
                            || get_class($combination->value_types['string']) === TNonFalsyString::class)
                        && get_class($type) === TNonEmptyLowercaseString::class
                    ) {
                        $combination->value_types['string'] = new TNonEmptyString();
                    } elseif (get_class($type) === TLowercaseString::class
                        && get_class($combination->value_types['string']) === TNonEmptyLowercaseString::class
                    ) {
                        $combination->value_types['string'] = $type;
                    } elseif (get_class($combination->value_types['string']) === TLowercaseString::class
                        && get_class($type) === TNonEmptyLowercaseString::class
                    ) {
                        //no-change
                    } elseif (get_class($combination->value_types['string'])
                            === TNonEmptyNonspecificLiteralString::class
                        && $type instanceof TNonEmptyString
                    ) {
                        $combination->value_types['string'] = new TNonEmptyString();
                    } elseif (get_class($type) === TNonEmptyNonspecificLiteralString::class
                        && $combination->value_types['string'] instanceof TNonEmptyString
                    ) {
                        // do nothing
                    } else {
                        $combination->value_types['string'] = new TString();
                    }
                }
            }

            $combination->strings = null;
        }
    }

    private static function scrapeIntProperties(
        string $type_key,
        Atomic $type,
        TypeCombination $combination,
        int $literal_limit
    ): void {
        if (isset($combination->value_types['array-key'])) {
            return;
        }

        $had_zero = isset($combination->ints['int(0)']);

        if ($type instanceof TLiteralInt) {
            if ($type->value === 0) {
                $had_zero = true;
            }

            if ($combination->ints !== null && count($combination->ints) < $literal_limit) {
                $combination->ints[$type_key] = $type;
            } else {
                $combination->ints[$type_key] = $type;

                $all_nonnegative = !array_filter(
                    $combination->ints,
                    function ($int): bool {
                        return $int->value < 0;
                    }
                );

                if (isset($combination->value_types['int'])) {
                    $current_int_type = $combination->value_types['int'];
                    if ($current_int_type instanceof TIntRange) {
                        foreach ($combination->ints as $int) {
                            if (!$current_int_type->contains($int->value)) {
                                $current_int_type->min_bound = TIntRange::getNewLowestBound(
                                    $current_int_type->min_bound,
                                    $int->value
                                );
                                $current_int_type->max_bound = TIntRange::getNewHighestBound(
                                    $current_int_type->max_bound,
                                    $int->value
                                );
                            }
                        }
                    }
                }

                $combination->ints = null;

                if (!isset($combination->value_types['int'])) {
                    $combination->value_types['int'] = $all_nonnegative
                        ? new TPositiveInt()
                        : new TNonspecificLiteralInt();
                } elseif ($combination->value_types['int'] instanceof TPositiveInt
                    && !$all_nonnegative
                ) {
                    $combination->value_types['int'] = new TInt();
                }
            }
        } else {
            if ($type instanceof TPositiveInt) {
                if ($combination->ints) {
                    $all_nonnegative = !array_filter(
                        $combination->ints,
                        function ($int): bool {
                            return $int->value < 0;
                        }
                    );

                    if ($all_nonnegative) {
                        $combination->value_types['int'] = $type;
                    } else {
                        $combination->value_types['int'] = new TInt();
                    }
                } elseif (!isset($combination->value_types['int'])) {
                    $combination->value_types['int'] = $type;
                } elseif ($combination->value_types['int'] instanceof TIntRange) {
                    //if we already had a range, we ensure the min is no higher than 1
                    $combination->value_types['int']->min_bound = TIntRange::getNewLowestBound(
                        $combination->value_types['int']->min_bound,
                        1
                    );
                    $combination->value_types['int']->max_bound = null;
                } elseif (get_class($combination->value_types['int']) !== get_class($type)) {
                    $combination->value_types['int'] = new TInt();
                }
            } elseif ($type instanceof TNonspecificLiteralInt) {
                if ($combination->ints || !isset($combination->value_types['int'])) {
                    $combination->value_types['int'] = $type;
                } elseif (isset($combination->value_types['int'])
                    && get_class($combination->value_types['int'])
                    !== get_class($type)
                ) {
                    $combination->value_types['int'] = new TInt();
                }
            } elseif ($type instanceof TIntRange) {
                $type = clone $type;
                if ($combination->ints) {
                    foreach ($combination->ints as $int) {
                        if (!$type->contains($int->value)) {
                            $type->min_bound = TIntRange::getNewLowestBound($type->min_bound, $int->value);
                            $type->max_bound = TIntRange::getNewHighestBound($type->max_bound, $int->value);
                        }
                    }

                    $combination->value_types['int'] = $type;
                } elseif (!isset($combination->value_types['int'])) {
                    $combination->value_types['int'] = $type;
                } else {
                    $old_type = $combination->value_types['int'];
                    if ($old_type instanceof TIntRange) {
                        $type->min_bound = TIntRange::getNewLowestBound($old_type->min_bound, $type->min_bound);
                        $type->max_bound = TIntRange::getNewHighestBound($old_type->max_bound, $type->max_bound);
                    } elseif ($old_type instanceof TPositiveInt) {
                        $type->min_bound = TIntRange::getNewLowestBound($type->min_bound, 0);
                        $type->max_bound = null;
                    } else {
                        $type = new TInt();
                    }
                    $combination->value_types['int'] = $type;
                }
            } else {
                $combination->value_types['int'] = $type;
            }

            $combination->ints = null;
        }

        if ($had_zero
            && isset($combination->value_types['int'])
            && $combination->value_types['int'] instanceof TPositiveInt
        ) {
            if ($combination->ints === null) {
                $combination->ints = ['int(0)' => new TLiteralInt(0)];
            } elseif ($type instanceof TLiteralInt && $type->value < 0) {
                $combination->ints = null;
                $combination->value_types['int'] = new TInt();
            }
        }
    }

    /**
     * @return array<string, bool>
     */
    private static function getSharedTypes(TypeCombination $combination, Codebase $codebase): array
    {
        /** @var array<string, bool>|null */
        $shared_classlikes = null;

        if ($combination->strings) {
            foreach ($combination->strings as $string_type) {
                $classlikes = self::getClassLikes($codebase, $string_type->value);

                if ($shared_classlikes === null) {
                    $shared_classlikes = $classlikes;
                } elseif ($shared_classlikes) {
                    $shared_classlikes = array_intersect_key($shared_classlikes, $classlikes);
                }
            }
        }

        if ($combination->class_string_types) {
            foreach ($combination->class_string_types as $value_type) {
                if ($value_type instanceof TNamedObject) {
                    $classlikes = self::getClassLikes($codebase, $value_type->value);

                    if ($shared_classlikes === null) {
                        $shared_classlikes = $classlikes;
                    } elseif ($shared_classlikes) {
                        $shared_classlikes = array_intersect_key($shared_classlikes, $classlikes);
                    }
                }
            }
        }

        return $shared_classlikes ?: [];
    }

    /**
     * @return array<string, true>
     */
    private static function getClassLikes(Codebase $codebase, string $fq_classlike_name): array
    {
        try {
            $class_storage = $codebase->classlike_storage_provider->get($fq_classlike_name);
        } catch (InvalidArgumentException $e) {
            return [];
        }

        $classlikes = [];

        $classlikes[$fq_classlike_name] = true;

        foreach ($class_storage->parent_classes as $parent_class) {
            $classlikes[$parent_class] = true;
        }

        foreach ($class_storage->parent_interfaces as $parent_interface) {
            $classlikes[$parent_interface] = true;
        }

        foreach ($class_storage->class_implements as $interface) {
            $classlikes[$interface] = true;
        }

        return $classlikes;
    }

    /**
     * @return list<Atomic>
     */
    private static function handleKeyedArrayEntries(
        TypeCombination $combination,
        bool $overwrite_empty_array
    ): array {
        $new_types = [];

        if ($combination->array_type_params
            && $combination->array_type_params[0]->allStringLiterals()
            && $combination->array_always_filled
        ) {
            foreach ($combination->array_type_params[0]->getAtomicTypes() as $atomic_key_type) {
                if ($atomic_key_type instanceof TLiteralString) {
                    $combination->objectlike_entries[$atomic_key_type->value]
                        = $combination->array_type_params[1];
                }
            }

            $combination->array_type_params = [];
            $combination->objectlike_sealed = false;
        }

        if (!$combination->array_type_params
            || $combination->array_type_params[1]->isEmpty()
        ) {
            if (!$overwrite_empty_array
                && ($combination->array_type_params
                    && ($combination->array_type_params[1]->isEmpty()
                        || $combination->array_type_params[1]->isMixed()))
            ) {
                foreach ($combination->objectlike_entries as $objectlike_entry) {
                    $objectlike_entry->possibly_undefined = true;
                }
            }

            if ($combination->objectlike_value_type
                && $combination->objectlike_value_type->isMixed()
            ) {
                $combination->objectlike_entries = array_filter(
                    $combination->objectlike_entries,
                    function (Union $type): bool {
                        return !$type->possibly_undefined;
                    }
                );
            }

            if ($combination->objectlike_entries) {
                if ($combination->all_arrays_callable) {
                    $objectlike = new TCallableKeyedArray($combination->objectlike_entries);
                } else {
                    $objectlike = new TKeyedArray($combination->objectlike_entries);
                }

                if ($combination->objectlike_sealed && !$combination->array_type_params) {
                    $objectlike->sealed = true;
                }

                if ($combination->objectlike_key_type) {
                    $objectlike->previous_key_type = $combination->objectlike_key_type;
                } elseif ($combination->array_type_params
                    && $combination->array_type_params[0]->isArrayKey()
                ) {
                    $objectlike->previous_key_type = $combination->array_type_params[0];
                }

                if ($combination->objectlike_value_type) {
                    $objectlike->previous_value_type = $combination->objectlike_value_type;
                } elseif ($combination->array_type_params
                    && $combination->array_type_params[1]->isMixed()
                ) {
                    $objectlike->previous_value_type = $combination->array_type_params[1];
                }

                if ($combination->all_arrays_lists) {
                    $objectlike->is_list = true;
                }

                $new_types[] = $objectlike;
            } else {
                $new_types[] = new TArray([Type::getArrayKey(), Type::getMixed()]);
            }

            // if we're merging an empty array with an object-like, clobber empty array
            $combination->array_type_params = [];
        }

        return $new_types;
    }

    /**
     * @param  array{Union, Union}  $generic_type_params
     */
    private static function getArrayTypeFromGenericParams(
        ?Codebase $codebase,
        TypeCombination $combination,
        bool $overwrite_empty_array,
        bool $allow_mixed_union,
        Atomic $type,
        array $generic_type_params
    ): Atomic {
        if ($combination->objectlike_entries) {
            $objectlike_generic_type = null;

            $objectlike_keys = [];

            foreach ($combination->objectlike_entries as $property_name => $property_type) {
                $objectlike_generic_type = Type::combineUnionTypes(
                    clone $property_type,
                    $objectlike_generic_type,
                    $codebase,
                    $overwrite_empty_array
                );

                if (is_int($property_name)) {
                    $objectlike_keys[$property_name] = new TLiteralInt($property_name);
                } elseif ($type instanceof TKeyedArray && isset($type->class_strings[$property_name])) {
                    $objectlike_keys[$property_name] = new TLiteralClassString($property_name);
                } else {
                    $objectlike_keys[$property_name] = new TLiteralString($property_name);
                }
            }

            if ($combination->objectlike_value_type) {
                $objectlike_generic_type = Type::combineUnionTypes(
                    $combination->objectlike_value_type,
                    $objectlike_generic_type,
                    $codebase,
                    $overwrite_empty_array
                );
            }

            $objectlike_generic_type->possibly_undefined = false;

            $objectlike_key_type = new Union(array_values($objectlike_keys));

            $objectlike_key_type = Type::combineUnionTypes(
                $combination->objectlike_key_type,
                $objectlike_key_type,
                $codebase,
                $overwrite_empty_array
            );

            $generic_type_params[0] = Type::combineUnionTypes(
                $generic_type_params[0],
                $objectlike_key_type,
                $codebase,
                $overwrite_empty_array,
                $allow_mixed_union
            );

            if (!$generic_type_params[1]->isMixed()) {
                $generic_type_params[1] = Type::combineUnionTypes(
                    $generic_type_params[1],
                    $objectlike_generic_type,
                    $codebase,
                    $overwrite_empty_array,
                    $allow_mixed_union
                );
            }
        }

        if ($combination->all_arrays_callable) {
            $array_type = new TCallableArray($generic_type_params);
        } elseif ($combination->array_always_filled
            || ($combination->array_sometimes_filled && $overwrite_empty_array)
            || ($combination->objectlike_entries
                && $combination->objectlike_sealed
                && $overwrite_empty_array)
        ) {
            if ($combination->all_arrays_lists) {
                if ($combination->objectlike_entries
                    && $combination->objectlike_sealed
                ) {
                    $array_type = new TKeyedArray([$generic_type_params[1]]);
                    $array_type->previous_key_type = Type::getInt();
                    $array_type->previous_value_type = $combination->array_type_params[1];
                    $array_type->is_list = true;
                } else {
                    $array_type = new TNonEmptyList($generic_type_params[1]);

                    if ($combination->array_counts && count($combination->array_counts) === 1) {
                        $array_type->count = array_keys($combination->array_counts)[0];
                    }
                }
            } else {
                $array_type = new TNonEmptyArray($generic_type_params);

                if ($combination->array_counts && count($combination->array_counts) === 1) {
                    $array_type->count = array_keys($combination->array_counts)[0];
                }
            }
        } else {
            if ($combination->all_arrays_class_string_maps
                && count($combination->class_string_map_as_types) === 1
                && count($combination->class_string_map_names) === 1
            ) {
                $array_type = new TClassStringMap(
                    array_keys($combination->class_string_map_names)[0],
                    array_values($combination->class_string_map_as_types)[0],
                    $generic_type_params[1]
                );
            } elseif ($combination->all_arrays_lists) {
                $array_type = new TList($generic_type_params[1]);
            } else {
                $array_type = new TArray($generic_type_params);
            }
        }

        return $array_type;
    }
}
