<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\JsonValueTreeTest;

use Pterodactyl\Support\JsonValueTree;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('maps string leaves without changing other json values', function () {
    $value = ['command' => 'run {{PORT}}', 'conditions' => ['{{HOST}}' => '127.0.0.1', 'enabled' => true], 'limit' => 10, 'nullable' => null];
    $tree = JsonValueTree::from($value);
    $mapped = $tree->mapStrings(static fn (string $value): string => str_replace(['{{PORT}}', '{{HOST}}'], ['25565', '0.0.0.0'], $value));
    expect($mapped->toValue())->toBe(['command' => 'run 25565', 'conditions' => ['{{HOST}}' => '127.0.0.1', 'enabled' => true], 'limit' => 10, 'nullable' => null]);
    // Mapping returns a new tree; the original input is untouched.
    expect($tree->toValue())->toBe($value);
});

test('counts only the direct children of json arrays', function () {
    expect(JsonValueTree::from(['nested' => [1, 2], 'empty' => []])->arraySize())->toBe(2);
    expect(JsonValueTree::from([])->arraySize())->toBe(0);
    expect(JsonValueTree::from('value')->arraySize())->toBeNull();
    expect(JsonValueTree::from(null)->arraySize())->toBeNull();
});
