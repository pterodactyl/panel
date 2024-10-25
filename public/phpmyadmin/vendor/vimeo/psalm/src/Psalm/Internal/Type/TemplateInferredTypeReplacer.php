<?php

namespace Psalm\Internal\Type;

use InvalidArgumentException;
use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Type;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_merge;
use function array_shift;
use function array_values;
use function strpos;

class TemplateInferredTypeReplacer
{
    /**
     * This replaces template types in unions with the inferred types they should be
     */
    public static function replace(
        Union $union,
        TemplateResult $template_result,
        ?Codebase $codebase
    ): void {
        $keys_to_unset = [];

        $new_types = [];

        $is_mixed = false;

        $inferred_lower_bounds = $template_result->lower_bounds ?: [];

        foreach ($union->getAtomicTypes() as $key => $atomic_type) {
            $atomic_type->replaceTemplateTypesWithArgTypes($template_result, $codebase);

            if ($atomic_type instanceof TTemplateParam) {
                $template_type = null;

                $traversed_type = TemplateStandinTypeReplacer::getRootTemplateType(
                    $inferred_lower_bounds,
                    $atomic_type->param_name,
                    $atomic_type->defining_class,
                    [],
                    $codebase
                );

                if ($traversed_type) {
                    $template_type = $traversed_type;

                    if (!$atomic_type->as->isMixed() && $template_type->isMixed()) {
                        $template_type = clone $atomic_type->as;
                    } else {
                        $template_type = clone $template_type;
                    }

                    if ($atomic_type->extra_types) {
                        foreach ($template_type->getAtomicTypes() as $template_type_key => $atomic_template_type) {
                            if ($atomic_template_type instanceof TNamedObject
                                || $atomic_template_type instanceof TTemplateParam
                                || $atomic_template_type instanceof TIterable
                                || $atomic_template_type instanceof TObjectWithProperties
                            ) {
                                $atomic_template_type->extra_types = array_merge(
                                    $atomic_type->extra_types,
                                    $atomic_template_type->extra_types ?: []
                                );
                            } elseif ($atomic_template_type instanceof TObject) {
                                $first_atomic_type = array_shift($atomic_type->extra_types);

                                if ($atomic_type->extra_types) {
                                    $first_atomic_type->extra_types = $atomic_type->extra_types;
                                }

                                $template_type->removeType($template_type_key);
                                $template_type->addType($first_atomic_type);
                            }
                        }
                    }
                } elseif ($codebase) {
                    foreach ($inferred_lower_bounds as $template_type_map) {
                        foreach ($template_type_map as $template_class => $_) {
                            if (strpos($template_class, 'fn-') === 0) {
                                continue;
                            }

                            try {
                                $classlike_storage = $codebase->classlike_storage_provider->get($template_class);

                                if ($classlike_storage->template_extended_params) {
                                    $defining_class = $atomic_type->defining_class;

                                    if (isset($classlike_storage->template_extended_params[$defining_class])) {
                                        $param_map = $classlike_storage->template_extended_params[$defining_class];

                                        if (isset($param_map[$key])
                                            && isset($inferred_lower_bounds[(string) $param_map[$key]][$template_class])
                                        ) {
                                            $template_name = (string) $param_map[$key];

                                            $template_type
                                                = clone TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                                                    $inferred_lower_bounds[$template_name][$template_class],
                                                    $codebase
                                                );
                                        }
                                    }
                                }
                            } catch (InvalidArgumentException $e) {
                            }
                        }
                    }
                }

                if ($template_type) {
                    $keys_to_unset[] = $key;

                    foreach ($template_type->getAtomicTypes() as $template_type_part) {
                        if ($template_type_part instanceof TMixed) {
                            $is_mixed = true;
                        }

                        $new_types[] = $template_type_part;
                    }
                }
            } elseif ($atomic_type instanceof TTemplateParamClass) {
                $template_type = isset($inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])
                    ? clone TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                        $inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
                        $codebase
                    )
                    : null;

                $class_template_type = null;

                if ($template_type) {
                    foreach ($template_type->getAtomicTypes() as $template_type_part) {
                        if ($template_type_part instanceof TMixed
                            || $template_type_part instanceof TObject
                        ) {
                            $class_template_type = new TClassString();
                        } elseif ($template_type_part instanceof TNamedObject) {
                            $class_template_type = new TClassString(
                                $template_type_part->value,
                                $template_type_part
                            );
                        } elseif ($template_type_part instanceof TTemplateParam) {
                            $first_atomic_type = $template_type_part->as->getSingleAtomic();

                            $class_template_type = new TTemplateParamClass(
                                $template_type_part->param_name,
                                $template_type_part->as->getId(),
                                $first_atomic_type instanceof TNamedObject ? $first_atomic_type : null,
                                $template_type_part->defining_class
                            );
                        }
                    }
                }

                if ($class_template_type) {
                    $keys_to_unset[] = $key;
                    $new_types[] = $class_template_type;
                }
            } elseif ($atomic_type instanceof TTemplateIndexedAccess) {
                $keys_to_unset[] = $key;

                $template_type = null;

                if (isset($inferred_lower_bounds[$atomic_type->array_param_name][$atomic_type->defining_class])
                    && !empty($inferred_lower_bounds[$atomic_type->offset_param_name])
                ) {
                    $array_template_type
                        = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                            $inferred_lower_bounds[$atomic_type->array_param_name][$atomic_type->defining_class],
                            $codebase
                        );

                    $offset_template_type
                        = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                            array_values($inferred_lower_bounds[$atomic_type->offset_param_name])[0],
                            $codebase
                        );

                    if ($array_template_type->isSingle()
                        && $offset_template_type->isSingle()
                        && !$array_template_type->isMixed()
                        && !$offset_template_type->isMixed()
                    ) {
                        $array_template_type = $array_template_type->getSingleAtomic();
                        $offset_template_type = $offset_template_type->getSingleAtomic();

                        if ($array_template_type instanceof TKeyedArray
                            && ($offset_template_type instanceof TLiteralString
                                || $offset_template_type instanceof TLiteralInt)
                            && isset($array_template_type->properties[$offset_template_type->value])
                        ) {
                            $template_type = clone $array_template_type->properties[$offset_template_type->value];
                        }
                    }
                }

                if ($template_type) {
                    foreach ($template_type->getAtomicTypes() as $template_type_part) {
                        if ($template_type_part instanceof TMixed) {
                            $is_mixed = true;
                        }

                        $new_types[] = $template_type_part;
                    }
                } else {
                    $new_types[] = new TMixed();
                }
            } elseif ($atomic_type instanceof TConditional
                && $codebase
            ) {
                $template_type = isset($inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])
                    ? clone TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                        $inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
                        $codebase
                    )
                    : null;

                $if_template_type = null;
                $else_template_type = null;

                $atomic_type = clone $atomic_type;

                if ($template_type) {
                    self::replace(
                        $atomic_type->as_type,
                        $template_result,
                        $codebase
                    );

                    if ($atomic_type->as_type->isNullable() && $template_type->isVoid()) {
                        $template_type = Type::getNull();
                    }

                    $matching_if_types = [];
                    $matching_else_types = [];

                    foreach ($template_type->getAtomicTypes() as $candidate_atomic_type) {
                        if (UnionTypeComparator::isContainedBy(
                            $codebase,
                            new Union([$candidate_atomic_type]),
                            $atomic_type->conditional_type,
                            false,
                            false,
                            null,
                            false,
                            false
                        )
                            && (!$candidate_atomic_type instanceof TInt
                                || $atomic_type->conditional_type->getId() !== 'float')
                        ) {
                            $matching_if_types[] = $candidate_atomic_type;
                        } elseif (!UnionTypeComparator::isContainedBy(
                            $codebase,
                            $atomic_type->conditional_type,
                            new Union([$candidate_atomic_type]),
                            false,
                            false,
                            null,
                            false,
                            false
                        )) {
                            $matching_else_types[] = $candidate_atomic_type;
                        }
                    }

                    $if_candidate_type = $matching_if_types ? new Union($matching_if_types) : null;
                    $else_candidate_type = $matching_else_types ? new Union($matching_else_types) : null;

                    if ($if_candidate_type
                        && UnionTypeComparator::isContainedBy(
                            $codebase,
                            $if_candidate_type,
                            $atomic_type->conditional_type,
                            false,
                            false,
                            null,
                            false,
                            false
                        )
                    ) {
                        $if_template_type = clone $atomic_type->if_type;

                        $refined_template_result = clone $template_result;

                        $refined_template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class]
                            = [
                            new TemplateBound(
                                $if_candidate_type
                            )
                        ];

                        self::replace(
                            $if_template_type,
                            $refined_template_result,
                            $codebase
                        );
                    }

                    if ($else_candidate_type
                        && UnionTypeComparator::isContainedBy(
                            $codebase,
                            $else_candidate_type,
                            $atomic_type->as_type,
                            false,
                            false,
                            null,
                            false,
                            false
                        )
                    ) {
                        $else_template_type = clone $atomic_type->else_type;

                        $refined_template_result = clone $template_result;

                        $refined_template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class]
                            = [
                            new TemplateBound(
                                $else_candidate_type
                            )
                        ];

                        self::replace(
                            $else_template_type,
                            $refined_template_result,
                            $codebase
                        );
                    }
                }

                if (!$if_template_type && !$else_template_type) {
                    self::replace(
                        $atomic_type->if_type,
                        $template_result,
                        $codebase
                    );

                    self::replace(
                        $atomic_type->else_type,
                        $template_result,
                        $codebase
                    );

                    $class_template_type = Type::combineUnionTypes(
                        $atomic_type->if_type,
                        $atomic_type->else_type,
                        $codebase
                    );
                } else {
                    $class_template_type = Type::combineUnionTypes(
                        $if_template_type,
                        $else_template_type,
                        $codebase
                    );
                }

                $keys_to_unset[] = $key;

                foreach ($class_template_type->getAtomicTypes() as $class_template_atomic_type) {
                    $new_types[] = $class_template_atomic_type;
                }
            }
        }

        $union->bustCache();

        if ($is_mixed) {
            if (!$new_types) {
                throw new UnexpectedValueException('This array should be full');
            }

            $union->replaceTypes(
                TypeCombiner::combine(
                    $new_types,
                    $codebase
                )->getAtomicTypes()
            );

            return;
        }

        foreach ($keys_to_unset as $key) {
            $union->removeType($key);
        }

        $atomic_types = array_values(array_merge($union->getAtomicTypes(), $new_types));

        $union->replaceTypes(
            TypeCombiner::combine(
                $atomic_types,
                $codebase
            )->getAtomicTypes()
        );
    }
}
