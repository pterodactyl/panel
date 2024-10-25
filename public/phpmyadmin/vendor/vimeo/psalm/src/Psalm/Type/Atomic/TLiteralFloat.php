<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a floating point value where the exact numeric value is known.
 */
class TLiteralFloat extends TFloat
{
    /** @var float */
    public $value;

    public function __construct(float $value)
    {
        $this->value = $value;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'float(' . $this->value . ')';
    }

    public function getId(bool $nested = false): string
    {
        return 'float(' . $this->value . ')';
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
        return 'float';
    }
}
