<?php

namespace Psalm\Type\Atomic;

use function substr;

/**
 * Represents the type that is the result of a bitmask combination of its parameters.
 * `int-mask<1, 2, 4>` corresponds to `0|1|2|3|4|5|6|7`
 */
class TIntMask extends TInt
{
    /** @var non-empty-array<TLiteralInt|TClassConstant> */
    public $values;

    /** @param non-empty-array<TLiteralInt|TClassConstant> $values */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function getKey(bool $include_extra = true): string
    {
        $s = '';

        foreach ($this->values as $value) {
            $s .= $value->getKey() . ', ';
        }

        return 'int-mask<' . substr($s, 0, -2) . '>';
    }

    public function getId(bool $nested = false): string
    {
        $s = '';

        foreach ($this->values as $value) {
            $s .= $value->getId() . ', ';
        }

        return 'int-mask<' . substr($s, 0, -2) . '>';
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
            return 'int';
        }

        $s = '';

        foreach ($this->values as $value) {
            $s .= $value->toNamespacedString($namespace, $aliased_classes, $this_class, false) . ', ';
        }

        return 'int-mask<' . substr($s, 0, -2) . '>';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
