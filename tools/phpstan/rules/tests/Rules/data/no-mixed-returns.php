<?php

declare(strict_types=1);

namespace Rules\Tests\Data\NoMixedReturns;

function nativeMixed(): mixed // error: line 7
{
    return 1;
}

/**
 * @return mixed
 */
function docblockMixed() // error: line 14
{
    return 1;
}

/**
 * @return \Generator<int, mixed>
 */
function generatorOfMixed(): \Generator // error: line 23
{
    yield 1;
}

/**
 * @return iterable<mixed>
 */
function iterableOfMixed(): iterable // error: line 30
{
    return [];
}

function typed(): string
{
    return 'a';
}

function nothing(): never
{
    throw new \RuntimeException('halt');
}

/**
 * @return \Generator<int, string>
 */
function generatorOfString(): \Generator
{
    yield 'a';
}

final class Bag
{
    /** @var array<string, string> */
    private array $items = [];

    public function __get(string $name): mixed
    {
        return $this->items[$name] ?? null;
    }

    public function grab(string $name): mixed // error: line 63
    {
        return $this->items[$name] ?? null;
    }
}

$closure = function (): mixed { // error: line 69
    return 1;
};
