<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Represents a list key created from foreach ($list as $key => $value)
 */
class TDependentListKey extends TInt implements DependentType
{
    /**
     * Used to hold information as to what list variable this refers to
     *
     * @var string
     */
    public $var_id;

    /**
     * @param string $var_id the variable id
     */
    public function __construct(string $var_id)
    {
        $this->var_id = $var_id;
    }

    public function getId(bool $nested = false): string
    {
        return 'list-key<' . $this->var_id . '>';
    }

    public function getVarId(): string
    {
        return $this->var_id;
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'int';
    }

    public function getReplacement(): Atomic
    {
        return new TInt();
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
