<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoChainedVarTags;

function rebrand(string $json): object
{
    $decoded = json_decode($json);

    /** @var object $decoded */
    $decoded;
    /** @var \DateTimeImmutable $decoded */
    $decoded; // error: line 14

    /** @var object $other */
    $other = $decoded;
    $used = $other;
    /** @var \DateTimeImmutable $other */
    $other;

    return $used;
}
