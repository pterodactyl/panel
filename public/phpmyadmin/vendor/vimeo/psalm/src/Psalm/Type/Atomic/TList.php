<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function get_class;

/**
 * Represents an array that has some particularities:
 * - its keys are integers
 * - they start at 0
 * - they are consecutive and go upwards (no negative int)
 */
class TList extends Atomic
{
    /**
     * @var Union
     */
    public $type_param;

    public const KEY = 'list';

    /**
     * Constructs a new instance of a list
     */
    public function __construct(Union $type_param)
    {
        $this->type_param = $type_param;
    }

    public function __toString(): string
    {
        /** @psalm-suppress MixedOperand */
        return static::KEY . '<' . $this->type_param . '>';
    }

    public function getId(bool $nested = false): string
    {
        /** @psalm-suppress MixedOperand */
        return static::KEY . '<' . $this->type_param->getId() . '>';
    }

    public function __clone()
    {
        $this->type_param = clone $this->type_param;
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
        if ($use_phpdoc_format) {
            return (new TArray([Type::getInt(), $this->type_param]))
                ->toNamespacedString(
                    $namespace,
                    $aliased_classes,
                    $this_class,
                    true
                );
        }

        /** @psalm-suppress MixedOperand */
        return static::KEY
            . '<'
            . $this->type_param->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                false
            )
            . '>';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): string {
        return 'array';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'array';
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
        $list = clone $this;

        foreach ([Type::getInt(), $list->type_param] as $offset => $type_param) {
            $input_type_param = null;

            if (($input_type instanceof TGenericObject
                    || $input_type instanceof TIterable
                    || $input_type instanceof TArray)
                &&
                    isset($input_type->type_params[$offset])
            ) {
                $input_type_param = clone $input_type->type_params[$offset];
            } elseif ($input_type instanceof TKeyedArray) {
                if ($offset === 0) {
                    $input_type_param = $input_type->getGenericKeyType();
                } else {
                    $input_type_param = $input_type->getGenericValueType();
                }
            } elseif ($input_type instanceof TList) {
                if ($offset === 0) {
                    continue;
                }

                $input_type_param = clone $input_type->type_param;
            }

            $type_param = TemplateStandinTypeReplacer::replace(
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
                null,
                $depth + 1
            );

            if ($offset === 1) {
                $list->type_param = $type_param;
            }
        }

        return $list;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): void {
        TemplateInferredTypeReplacer::replace(
            $this->type_param,
            $template_result,
            $codebase
        );
    }

    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if (get_class($other_type) !== static::class) {
            return false;
        }

        if (!$this->type_param->equals($other_type->type_param, $ensure_source_equality)) {
            return false;
        }

        return true;
    }

    public function getAssertionString(bool $exact = false): string
    {
        if (!$exact || $this->type_param->isMixed()) {
            return 'list';
        }

        return $this->toNamespacedString(null, [], null, false);
    }

    public function getChildNodes(): array
    {
        return [$this->type_param];
    }
}
