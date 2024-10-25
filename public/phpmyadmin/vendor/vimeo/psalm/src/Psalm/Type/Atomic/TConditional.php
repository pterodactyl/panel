<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Internal representation of a conditional return type in phpdoc. For example ($param1 is int ? int : string)
 */
class TConditional extends Atomic
{
    /**
     * @var string
     */
    public $param_name;

    /**
     * @var string
     */
    public $defining_class;

    /**
     * @var Union
     */
    public $as_type;

    /**
     * @var Union
     */
    public $conditional_type;

    /**
     * @var Union
     */
    public $if_type;

    /**
     * @var Union
     */
    public $else_type;

    public function __construct(
        string $param_name,
        string $defining_class,
        Union $as_type,
        Union $conditional_type,
        Union $if_type,
        Union $else_type
    ) {
        $this->param_name = $param_name;
        $this->defining_class = $defining_class;
        $this->as_type = $as_type;
        $this->conditional_type = $conditional_type;
        $this->if_type = $if_type;
        $this->else_type = $else_type;
    }

    public function __toString(): string
    {
        return '('
            . $this->param_name
            . ' is ' . $this->conditional_type
            . ' ? ' . $this->if_type
            . ' : ' . $this->else_type
            . ')';
    }

    public function __clone()
    {
        $this->conditional_type = clone $this->conditional_type;
        $this->if_type = clone $this->if_type;
        $this->else_type = clone $this->else_type;
        $this->as_type = clone $this->as_type;
    }

    public function getKey(bool $include_extra = true): string
    {
        return $this->__toString();
    }

    public function getAssertionString(bool $exact = false): string
    {
        return '';
    }

    public function getId(bool $nested = false): string
    {
        return '('
            . $this->param_name . ':' . $this->defining_class
            . ' is ' . $this->conditional_type->getId()
            . ' ? ' . $this->if_type->getId()
            . ' : ' . $this->else_type->getId()
            . ')';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     *
     * @return null
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        return null;
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
        return '';
    }

    public function getChildNodes(): array
    {
        return [$this->conditional_type, $this->if_type, $this->else_type];
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): void {
        TemplateInferredTypeReplacer::replace(
            $this->conditional_type,
            $template_result,
            $codebase
        );
    }
}
