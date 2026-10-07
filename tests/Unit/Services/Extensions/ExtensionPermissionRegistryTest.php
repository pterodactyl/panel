<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionPermissionRegistryTest;

use InvalidArgumentException;
use Pterodactyl\Services\Extensions\ExtensionPermissionRegistry;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
test('registers an extension permission group under ext.<id>', function (): void {
    $registry = new ExtensionPermissionRegistry;
    $registry->register('votes', 'Manage server votes.', ['view' => 'View votes.', 'reset-all' => 'Reset every vote.']);

    expect($registry->all())->toBe([
        'ext.votes' => ['description' => 'Manage server votes.', 'keys' => ['view' => 'View votes.', 'reset-all' => 'Reset every vote.']],
    ]);
});
test('rejects permission keys that would not form a valid permission', function (array $keys): void {
    expect(fn () => (new ExtensionPermissionRegistry)->register('votes', 'Votes.', $keys))->toThrow(InvalidArgumentException::class);
})->with([
    'no keys' => [[]],
    'dotted key' => [['votes.view' => 'View votes.']],
    'uppercase key' => [['View' => 'View votes.']],
    'wildcard key' => [['*' => 'Everything.']],
    'trailing newline' => [["view\n" => 'View votes.']],
]);
