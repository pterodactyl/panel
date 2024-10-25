<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Represents a string whose value is that of a type found by get_debug_type($var)
 */
class TDependentGetDebugType extends TString implements DependentType
{
    /**
     * Used to hold information as to what this refers to
     *
     * @var string
     */
    public $typeof;

    /**
     * @param string $typeof the variable id
     */
    public function __construct(string $typeof)
    {
        $this->typeof = $typeof;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'get-debug-type-of<' . $this->typeof . '>';
    }

    public function getVarId(): string
    {
        return $this->typeof;
    }

    public function getReplacement(): Atomic
    {
        return new TString();
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
