<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoUnsafeDictionaryType;

/**
 * @param array<string, mixed> $meta
 */
function mixedValues(array $meta): void {} // error: line 10

/**
 * @param array<string, object> $bag
 */
function objectValues(array $bag): void {} // error: line 15

/**
 * @param array<string, array> $nested
 */
function untypedArrayValues(array $nested): void {} // error: line 20

/**
 * @param list<mixed> $items
 */
function mixedList(array $items): void {} // error: line 25

/**
 * @param iterable<string, mixed> $stream
 */
function mixedIterable(iterable $stream): void {} // error: line 30

/**
 * @param array<string, string|object> $unionValues
 */
function unionEscape(array $unionValues): void {} // error: line 35

/**
 * @param array<string, \stdClass> $rows
 */
function stdClassValues(array $rows): void {} // error: line 40

/**
 * @return array<string, mixed>
 */
function returnsMixedDict(): array // error: line 45
{
    return [];
}

/**
 * @param array<string, string> $safe
 * @param array{id: int, meta: mixed} $shape
 * @param list<\DateTimeImmutable> $moments
 */
function fine(array $safe, array $shape, array $moments): void {}
