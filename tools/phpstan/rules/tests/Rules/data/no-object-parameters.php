<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoObjectParameters;

function nativeObject(object $input): void {} // error: line 7

/**
 * @param object $payload
 */
function docblockObject($payload): void {} // error: line 12

function nullableObject(?object $maybe): void {} // error: line 14

function named(\DateTimeImmutable $moment): void {}

/**
 * @template T of object
 * @param T $subject
 */
function generic(object $subject): void {}

final class Handler
{
    public function apply(object $target): void {} // error: line 26
}

$closure = function (object $thing): void {}; // error: line 29
