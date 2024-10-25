<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_map;
use function array_values;
use function count;
use function implode;
use function strpos;
use function substr;

trait GenericTrait
{
    public function __toString(): string
    {
        $s = '';
        foreach ($this->type_params as $type_param) {
            $s .= $type_param . ', ';
        }

        $extra_types = '';

        if ($this instanceof TNamedObject && $this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        return $this->value . '<' . substr($s, 0, -2) . '>' . $extra_types;
    }

    public function getId(bool $nested = false): string
    {
        $s = '';
        foreach ($this->type_params as $type_param) {
            $s .= $type_param->getId() . ', ';
        }

        $extra_types = '';

        if ($this instanceof TNamedObject) {
            if ($this->extra_types) {
                $extra_types = '&' . implode(
                    '&',
                    array_map(
                        function ($type) {
                            return $type->getId(true);
                        },
                        $this->extra_types
                    )
                );
            }

            if ($this->was_static) {
                $extra_types .= '&static';
            }
        }

        return $this->value . '<' . substr($s, 0, -2) . '>' . $extra_types;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        $base_value = $this instanceof TNamedObject
            ? parent::toNamespacedString($namespace, $aliased_classes, $this_class, $use_phpdoc_format)
            : $this->value;

        if ($base_value === 'non-empty-array') {
            $base_value = 'array';
        }

        if ($use_phpdoc_format) {
            if ($this instanceof TNamedObject || $this instanceof TIterable) {
                return $base_value;
            }

            $value_type = $this->type_params[1];

            if ($value_type->isMixed() || $value_type->isEmpty()) {
                return $base_value;
            }

            $value_type_string = $value_type->toNamespacedString($namespace, $aliased_classes, $this_class, true);

            if (!$value_type->isSingle()) {
                return '(' . $value_type_string . ')[]';
            }

            return $value_type_string . '[]';
        }

        $intersection_pos = strpos($base_value, '&');
        if ($intersection_pos !== false) {
            $base_value = substr($base_value, 0, $intersection_pos);
        }
        $type_params = $this->type_params;

        //no need for special format if the key is not determined
        if ($this instanceof TArray &&
            count($type_params) === 2 &&
            isset($type_params[0]) &&
            $type_params[0]->isArrayKey()
        ) {
            //we remove the key for display
            unset($type_params[0]);
            $type_params = array_values($type_params);
        }

        if ($this instanceof TArray &&
            count($type_params) === 1 &&
            isset($type_params[0]) &&
            $type_params[0]->isMixed()
        ) {
            //when the value of an array is mixed, no need for namespaced phpdoc
            return 'array';
        }

        $extra_types = '';

        if ($this instanceof TNamedObject && $this->extra_types) {
            $extra_types = '&' . implode(
                '&',
                array_map(
                    /**
                     * @return string
                     */
                    function (Atomic $extra_type) use ($namespace, $aliased_classes, $this_class): string {
                        return $extra_type->toNamespacedString($namespace, $aliased_classes, $this_class, false);
                    },
                    $this->extra_types
                )
            );
        }

        return $base_value .
                '<' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @return string
                         */
                        function (Union $type_param) use ($namespace, $aliased_classes, $this_class): string {
                            return $type_param->toNamespacedString($namespace, $aliased_classes, $this_class, false);
                        },
                        $type_params
                    )
                ) .
                '>' . $extra_types;
    }

    public function __clone()
    {
        foreach ($this->type_params as &$type_param) {
            $type_param = clone $type_param;
        }
    }

    /**
     * @return array<TypeNode>
     */
    public function getChildNodes(): array
    {
        return $this->type_params;
    }

    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        ?Codebase $codebase = null,
        ?StatementsAnalyzer $statements_analyzer = null,
        ?Atomic $input_type = null,
        ?int $input_arg_offset = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_lower_bound = false,
        int $depth = 0
    ): Atomic {
        if ($input_type instanceof TList) {
            $input_type = new TArray([Type::getInt(), $input_type->type_param]);
        }

        $input_object_type_params = [];

        $container_type_params_covariant = [];

        if ($input_type instanceof TGenericObject
            && ($this instanceof TGenericObject || $this instanceof TIterable)
            && $codebase
        ) {
            $input_object_type_params = TemplateStandinTypeReplacer::getMappedGenericTypeParams(
                $codebase,
                $input_type,
                $this,
                $container_type_params_covariant
            );
        }

        $atomic = clone $this;

        foreach ($atomic->type_params as $offset => $type_param) {
            $input_type_param = null;

            if (($input_type instanceof TIterable
                    || $input_type instanceof TArray)
                &&
                    isset($input_type->type_params[$offset])
            ) {
                $input_type_param = $input_type->type_params[$offset];
            } elseif ($input_type instanceof TKeyedArray) {
                if ($offset === 0) {
                    $input_type_param = $input_type->getGenericKeyType();
                } elseif ($offset === 1) {
                    $input_type_param = $input_type->getGenericValueType();
                } else {
                    throw new UnexpectedValueException('Not expecting offset of ' . $offset);
                }
            } elseif ($input_type instanceof TNamedObject
                && isset($input_object_type_params[$offset])
            ) {
                $input_type_param = $input_object_type_params[$offset];
            }

            /** @psalm-suppress PropertyTypeCoercion */
            $atomic->type_params[$offset] = TemplateStandinTypeReplacer::replace(
                $type_param,
                $template_result,
                $codebase,
                $statements_analyzer,
                $input_type_param,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_lower_bound,
                !($container_type_params_covariant[$offset] ?? true)
                    && $this instanceof TGenericObject
                    ? $this->value
                    : null,
                $depth + 1
            );
        }

        return $atomic;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): void {
        foreach ($this->type_params as $offset => $type_param) {
            TemplateInferredTypeReplacer::replace(
                $type_param,
                $template_result,
                $codebase
            );

            if ($this instanceof TArray && $offset === 0 && $type_param->isMixed()) {
                $this->type_params[0] = Type::getArrayKey();
            }
        }

        if ($this instanceof TGenericObject) {
            $this->remapped_params = true;
        }

        if ($this instanceof TGenericObject || $this instanceof TIterable) {
            $this->replaceIntersectionTemplateTypesWithArgTypes($template_result, $codebase);
        }
    }
}
