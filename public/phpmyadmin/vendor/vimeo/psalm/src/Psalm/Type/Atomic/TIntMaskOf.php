<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Represents the type that is the result of a bitmask combination of its parameters.
 * This is the same concept as TIntMask but TIntMaskOf is used with with a reference to constants in code
 * `int-mask<MyClass::CLASS_CONSTANT_*>` will corresponds to `0|1|2|3|4|5|6|7` if there are three constant 1, 2 and 4
 */
class TIntMaskOf extends TInt
{
    /** @var TClassConstant|TKeyOfClassConstant|TValueOfClassConstant */
    public $value;

    /**
     * @param TClassConstant|TKeyOfClassConstant|TValueOfClassConstant $value
     */
    public function __construct(Atomic $value)
    {
        $this->value = $value;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int-mask-of<' . $this->value->getKey() . '>';
    }

    public function getId(bool $nested = false): string
    {
        return $this->getKey();
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
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

        return 'int-mask-of<'
            . $this->value->toNamespacedString($namespace, $aliased_classes, $this_class, false)
            . '>';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
