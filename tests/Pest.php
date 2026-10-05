<?php

declare(strict_types=1);

use Pest\Support\HigherOrderTapProxy;
use PHPUnit\Framework\TestCase;

function pterodactylTestCase(): TestCase
{
    $test = test();

    if (! $test instanceof HigherOrderTapProxy) {
        throw new LogicException('No Pest test case is currently running.');
    }

    return $test->target;
}
