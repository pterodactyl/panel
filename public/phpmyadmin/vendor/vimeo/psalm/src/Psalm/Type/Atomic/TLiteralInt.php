<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an integer value where the exact numeric value is known.
 */
class TLiteralInt extends TInt
{
    /** @var int */
    public $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int(' . $this->value . ')';
    }

    public function getId(bool $nested = false): string
    {
        return (string) $this->value;
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'int(' . $this->value . ')';
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
        return $use_phpdoc_format ? 'int' : (string) $this->value;
    }
}
