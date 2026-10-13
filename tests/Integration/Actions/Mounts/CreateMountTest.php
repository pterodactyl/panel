<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Mounts\CreateMountTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Pterodactyl\Contracts\Mounts\CreatesMounts;
use Pterodactyl\Models\Mount;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, bool|int|string|null> */
function mount(array $overrides = []): array
{
    return [...['name' => 'Direct', 'source' => '/mnt/direct-source', 'target' => '/mnt/direct-target'], ...$overrides];
}

test('mounts created without the http api meet the same rules', function (array $input, string $field): void {
    Mount::factory()->create(['name' => 'Taken']);
    $count = Mount::query()->count();

    try {
        $this->app->make(CreatesMounts::class)->create(mount($input));
        $this->fail('The mount was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect(Mount::query()->count())->toBe($count);
})->with([
    'a missing name' => [['name' => null], 'name'],
    'a one letter name' => [['name' => 'a'], 'name'],
    'a taken name' => [['name' => 'Taken'], 'name'],
    'a description that is too long' => [['description' => str_repeat('a', 192)], 'description'],
    'a missing source' => [['source' => null], 'source'],
    'a relative source' => [['source' => 'mnt/source'], 'source'],
    'a source inside the wings configuration' => [['source' => '/etc/pterodactyl/config.yml'], 'source'],
    'a source that resolves to a whole volumes directory' => [['source' => '/var/lib/pterodactyl/volumes/abc/..'], 'source'],
    'a missing target' => [['target' => null], 'target'],
    'a target that is the container directory' => [['target' => '/home/container/'], 'target'],
    'a read only flag that is not a boolean' => [['read_only' => 'yes'], 'read_only'],
    'a user mountable flag that is not a boolean' => [['user_mountable' => 2], 'user_mountable'],
]);

test('values are coerced the way laravel validates them', function (int|string $flag, bool $expected): void {
    $mount = $this->app->make(CreatesMounts::class)->create(mount(['read_only' => $flag, 'user_mountable' => $flag]));

    $mount->refresh();
    expect($mount->read_only)->toBe($expected)
        ->and($mount->user_mountable)->toBe($expected);
})->with([[1, true], ['1', true], [0, false], ['0', false]]);

test('a mount keeps its paths as given and gets a uuid', function (): void {
    $mount = $this->app->make(CreatesMounts::class)->create(mount([
        'source' => '/var/lib/pterodactyl/volumes/34eddb08-887e-4a6e-b0f1-e4e249b3181c/plugins',
        'target' => '/home/container/plugins',
        'description' => '',
    ]));

    $mount->refresh();
    expect($mount->source)->toBe('/var/lib/pterodactyl/volumes/34eddb08-887e-4a6e-b0f1-e4e249b3181c/plugins')
        ->and($mount->target)->toBe('/home/container/plugins')
        ->and($mount->description)->toBeNull()
        ->and($mount->uuid)->toBeUuid();
});

test('extension field values that no request validated are refused', function (): void {
    expect(fn () => $this->app->make(CreatesMounts::class)->create(mount(['extensions' => ['billing' => ['plan' => 'pro']]])))
        ->toThrow(InvalidArgumentException::class, 'must come from a request that validated them');
});
