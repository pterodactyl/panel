<?php

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_map;
use function implode;

/**
 * denotes a template parameter that has been previously specified in a `@template` tag.
 */
class TTemplateParam extends Atomic
{
    use HasIntersectionTrait;

    /**
     * @var string
     */
    public $param_name;

    /**
     * @var Union
     */
    public $as;

    /**
     * @var string
     */
    public $defining_class;

    public function __construct(string $param_name, Union $extends, string $defining_class)
    {
        $this->param_name = $param_name;
        $this->as = $extends;
        $this->defining_class = $defining_class;
    }

    public function __toString(): string
    {
        return $this->param_name;
    }

    public function getKey(bool $include_extra = true): string
    {
        if ($include_extra && $this->extra_types) {
            return $this->param_name . ':' . $this->defining_class . '&' . implode('&', $this->extra_types);
        }

        return $this->param_name . ':' . $this->defining_class;
    }

    public function getAssertionString(bool $exact = false): string
    {
        return $this->as->getId();
    }

    public function getId(bool $nested = false): string
    {
        if ($this->extra_types) {
            return '(' . $this->param_name . ':' . $this->defining_class . ' as ' . $this->as->getId()
                . ')&' . implode('&', array_map(function ($type) {
                    return $type->getId(true);
                }, $this->extra_types));
        }

        return ($nested ? '(' : '') . $this->param_name
            . ':' . $this->defining_class
            . ' as ' . $this->as->getId() . ($nested ? ')' : '');
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
        if ($use_phpdoc_format) {
            return $this->as->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                true
            );
        }

        $intersection_types = $this->getNamespacedIntersectionTypes(
            $namespace,
            $aliased_classes,
            $this_class,
            false
        );

        return $this->param_name . $intersection_types;
    }

    public function getChildNodes(): array
    {
        return [$this->as];
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ): void {
        $this->replaceIntersectionTemplateTypesWithArgTypes($template_result, $codebase);
    }
}
