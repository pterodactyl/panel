<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoInstanceofOnMixed;

function sniff(mixed $value): bool
{
    return $value instanceof \DateTimeImmutable; // error: line 9
}

/**
 * @phpstan-assert-if-true \DateTimeImmutable $value
 */
function isMoment(mixed $value): bool
{
    return $value instanceof \DateTimeImmutable;
}

function typed(\Throwable $error): bool
{
    return $error instanceof \RuntimeException;
}
