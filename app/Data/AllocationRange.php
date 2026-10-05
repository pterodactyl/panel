<?php

declare(strict_types=1);

namespace Pterodactyl\Data;

use InvalidArgumentException;

final readonly class AllocationRange
{
    public function __construct(
        public int $start,
        public int $end,
    ) {}

    /**
     * @param  JsonInputValue  $start
     * @param  JsonInputValue  $end
     */
    public static function fromConfig(mixed $start, mixed $end): ?self
    {
        if (! $start || ! $end) {
            return null;
        }

        return new self(
            self::integerLike($start, 'start'),
            self::integerLike($end, 'end'),
        );
    }

    /** @param JsonInputValue $value */
    private static function integerLike(mixed $value, string $name): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            // SAFETY: the anchored decimal-integer pattern proves the string has an integer representation.
            return (int) $value;
        }

        throw new InvalidArgumentException(sprintf(
            'Expected allocation range %s to be an integer-like value.',
            $name,
        ));
    }
}
