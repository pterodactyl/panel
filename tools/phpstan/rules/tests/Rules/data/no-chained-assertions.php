<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoChainedAssertions;

function chained(string $raw): object
{
    $converted = (object) (array) $raw; // error: line 9

    $tripled = (string) (int) (float) $raw; // errors: lines 11 (outer) and 11 (middle)

    $single = (array) $raw;

    return (object) [$converted, $tripled, $single];
}
