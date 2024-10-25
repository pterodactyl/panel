<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a `class-string` corresponding to a template parameter previously specified in a `@template` tag.
 */
class TTemplateParamClass extends TClassString
{
    /**
     * @var string
     */
    public $param_name;

    /**
     * @var string
     */
    public $defining_class;

    public function __construct(
        string $param_name,
        string $as,
        ?TNamedObject $as_type,
        string $defining_class
    ) {
        $this->param_name = $param_name;
        $this->as = $as;
        $this->as_type = $as_type;
        $this->defining_class = $defining_class;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'class-string<' . $this->param_name . '>';
    }

    public function __toString(): string
    {
        return 'class-string<' . $this->param_name . '>';
    }

    public function getId(bool $nested = false): string
    {
        return 'class-string<' . $this->param_name . ':' . $this->defining_class
            . ' as ' . ($this->as_type ? $this->as_type->getId() : $this->as) . '>';
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'class-string<' . $this->param_name . '>';
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
        return $this->param_name . '::class';
    }
}
