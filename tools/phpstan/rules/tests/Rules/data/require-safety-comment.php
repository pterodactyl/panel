<?php

declare(strict_types=1);

namespace Rules\Tests\Data\RequireSafetyComment;

use Webmozart\Assert\Assert;

/**
 * @param array{id: string} $payload
 */
function handle(array $payload, string $raw, object $thing): int
{
    $bare = (int) $raw; // error: line 14

    // SAFETY: The regex above guarantees a digit-only string.
    $justified = (int) $raw;

    /** @var positive-int $count */
    $count = $bare; // error: line 19 (inline @var, no SAFETY)

    /** @var positive-int $total SAFETY: validated by the request rules. */
    $total = $justified;

    assert(is_string($payload['id'])); // error: line 25

    // SAFETY: only string ids reach this branch.
    assert(is_string($payload['id']));

    Assert::isInstanceOf($thing, \DateTimeImmutable::class); // error: line 30

    $sum = $count + $total;

    return $sum + (int) $bare; // dead cast on int: not flagged
}
