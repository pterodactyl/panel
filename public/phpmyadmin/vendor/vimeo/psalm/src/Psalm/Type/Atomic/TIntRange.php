<?php

namespace Psalm\Type\Atomic;

use function max;
use function min;

/**
 * Denotes an interval of integers between two bounds
 */
class TIntRange extends TInt
{
    public const BOUND_MIN = 'min';
    public const BOUND_MAX = 'max';

    /**
     * @var int|null
     */
    public $min_bound;
    /**
     * @var int|null
     */
    public $max_bound;

    public function __construct(?int $min_bound, ?int $max_bound)
    {
        $this->min_bound = $min_bound;
        $this->max_bound = $max_bound;
    }

    public function __toString(): string
    {
        return $this->getKey();
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int<' . ($this->min_bound ?? 'min') . ', ' . ($this->max_bound ?? 'max') . '>';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
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
        return $use_phpdoc_format ?
            'int' :
            'int<' . ($this->min_bound ?? 'min') . ', ' . ($this->max_bound ?? 'max') . '>';
    }

    public function isPositive(): bool
    {
        return $this->min_bound !== null && $this->min_bound > 0;
    }

    public function isNegative(): bool
    {
        return $this->max_bound !== null && $this->max_bound < 0;
    }

    public function isPositiveOrZero(): bool
    {
        return $this->min_bound !== null && $this->min_bound >= 0;
    }

    public function isNegativeOrZero(): bool
    {
        return $this->max_bound !== null && $this->max_bound <= 0;
    }

    public function contains(int $i): bool
    {
        return
            ($this->min_bound === null && $this->max_bound === null) ||
            ($this->min_bound === null && $this->max_bound >= $i) ||
            ($this->max_bound === null && $this->min_bound <= $i) ||
            ($this->min_bound <= $i && $this->max_bound >= $i);
    }

    /**
     * Returns true if every part of the Range is lesser than the given value
     */
    public function isLesserThan(int $i): bool
    {
        return $this->max_bound !== null && $this->max_bound < $i;
    }

    /**
     * Returns true if every part of the Range is greater than the given value
     */
    public function isGreaterThan(int $i): bool
    {
        return $this->min_bound !== null && $this->min_bound > $i;
    }

    public static function getNewLowestBound(?int $bound1, ?int $bound2): ?int
    {
        if ($bound1 === null || $bound2 === null) {
            return null;
        }
        return min($bound1, $bound2);
    }

    public static function getNewHighestBound(?int $bound1, ?int $bound2): ?int
    {
        if ($bound1 === null || $bound2 === null) {
            return null;
        }
        return max($bound1, $bound2);
    }

    /**
     * convert any int to its equivalent in int range
     */
    public static function convertToIntRange(TInt $int_atomic): TIntRange
    {
        if ($int_atomic instanceof TIntRange) {
            return $int_atomic;
        }

        if ($int_atomic instanceof TPositiveInt) {
            return new TIntRange(1, null);
        }

        if ($int_atomic instanceof TLiteralInt) {
            return new TIntRange($int_atomic->value, $int_atomic->value);
        }

        return new TIntRange(null, null);
    }

    public static function intersectIntRanges(TIntRange $int_range1, TIntRange $int_range2): ?TIntRange
    {
        if ($int_range1->min_bound === null || $int_range2->min_bound === null) {
            $new_min_bound = $int_range1->min_bound ?? $int_range2->min_bound;
        } else {
            $new_min_bound = self::getNewHighestBound($int_range1->min_bound, $int_range2->min_bound);
        }

        if ($int_range1->max_bound === null || $int_range2->max_bound === null) {
            $new_max_bound = $int_range1->max_bound ?? $int_range2->max_bound;
        } else {
            $new_max_bound = self::getNewLowestBound($int_range1->max_bound, $int_range2->max_bound);
        }

        if ($new_min_bound !== null && $new_max_bound !== null && $new_min_bound > $new_max_bound) {
            return null;
        }

        return new self($new_min_bound, $new_max_bound);
    }
}
