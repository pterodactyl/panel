<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoForbiddenTerms;

/**
 * @phpstan-type UserShape array{id: int}
 */
final class ResponseShape // errors: alias UserShape (line 10) + class name (line 10)
{
    public const SHAPE_VERSION = 1; // error: line 12

    /** @var array{id: int}|null */
    public ?array $payloadShape = null; // error: line 15

    public function shapeOf(int $shaped): int // errors: method (line 17) + param (line 17)
    {
        $resultShape = $shaped + 1; // errors: $resultShape (line 19) + read of $shaped (line 19)

        return $resultShape; // error: line 21
    }
}

final class UserSummary
{
    public function total(int $count): int
    {
        return $count;
    }
}
