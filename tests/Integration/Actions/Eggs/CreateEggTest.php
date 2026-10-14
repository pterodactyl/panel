<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Eggs\CreateEggTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Eggs\CreatesEggs;
use Pterodactyl\Models\Egg;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, mixed> */
function egg(array $overrides = []): array
{
    return [...[
        'name' => 'Direct Egg',
        'docker_images' => "Java 21|ghcr.io/pterodactyl/yolks:java_21\nghcr.io/pterodactyl/yolks:java_17",
        'startup' => 'java -jar server.jar',
        'config_stop' => 'stop',
        'config_startup' => '{"done": ["Done"]}',
        'config_logs' => '{}',
        'config_files' => '{}',
    ], ...$overrides];
}

test('eggs created without the http api meet the same rules', function (array $input, string $field): void {
    $count = Egg::query()->count();

    try {
        $this->app->make(CreatesEggs::class)->create(egg($input));
        $this->fail('The egg was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect(Egg::query()->count())->toBe($count);
})->with([
    'a missing name' => [['name' => null], 'name'],
    'a name that is too long' => [['name' => str_repeat('a', 192)], 'name'],
    'docker images given as a list' => [['docker_images' => ['ghcr.io/pterodactyl/yolks:java_21']], 'docker_images'],
    'a docker image line with shell characters' => [['docker_images' => "ghcr.io/pterodactyl/yolks:java_21\nghcr.io/x;rm -rf /"], 'docker_images'],
    'only blank docker image lines' => [['docker_images' => "\n \n"], 'docker_images'],
    'an outgoing ip flag that is not a boolean' => [['force_outgoing_ip' => 'yes'], 'force_outgoing_ip'],
    'features that are not a list' => [['features' => 'eula'], 'features'],
    'a feature that is not a string' => [['features' => ['eula', 5]], 'features.1'],
    'a denied file that is not a string' => [['file_denylist' => [['nested']]], 'file_denylist.0'],
    'a missing startup command' => [['startup' => ''], 'startup'],
    'a parent egg that does not exist' => [['config_from' => 999999], 'config_from'],
    'a missing stop command without a parent egg' => [['config_stop' => null], 'config_stop'],
    'a stop command that is too long' => [['config_stop' => str_repeat('a', 192)], 'config_stop'],
    'startup config that is not json' => [['config_startup' => '{done'], 'config_startup'],
    'files config that is not json' => [['config_files' => 'files'], 'config_files'],
]);

test('an egg is created with its images keyed by name and the service author', function (): void {
    config()->set('pterodactyl.service.author', 'eggs@example.com');

    $egg = $this->app->make(CreatesEggs::class)->create(egg([
        'description' => '',
        'features' => ['eula', 'fastdl'],
        'file_denylist' => ['secret.txt'],
    ]));

    expect($egg->author)->toBe('eggs@example.com')
        ->and($egg->docker_images)->toBe(['Java 21' => 'ghcr.io/pterodactyl/yolks:java_21', 'ghcr.io/pterodactyl/yolks:java_17' => 'ghcr.io/pterodactyl/yolks:java_17'])
        ->and($egg->description)->toBeNull()
        ->and($egg->features)->toBe(['eula', 'fastdl'])
        ->and($egg->file_denylist)->toBe(['secret.txt'])
        ->and($egg->force_outgoing_ip)->toBeFalse()
        ->and($egg->config_from)->toBeNull()
        ->and($egg->config_startup)->toBe('{"done": ["Done"]}');
});

test('values are coerced the way laravel validates them', function (int|string $flag, bool $expected): void {
    $parent = Egg::factory()->create();

    $egg = $this->app->make(CreatesEggs::class)->create([
        'name' => 'Child Egg',
        'docker_images' => 'ghcr.io/pterodactyl/yolks:java_21',
        'startup' => 'java -jar server.jar',
        'force_outgoing_ip' => $flag,
        'config_from' => (string) $parent->id,
    ]);

    expect($egg->force_outgoing_ip)->toBe($expected)
        ->and($egg->config_from)->toBe($parent->id)
        ->and($egg->config_stop)->toBeNull()
        ->and($egg->features)->toBe([]);
})->with([[1, true], ['1', true], [0, false], ['0', false]]);

test('a parent egg of zero means none, so the egg needs its own config', function (int|string $none): void {
    $eggs = $this->app->make(CreatesEggs::class);

    try {
        $eggs->create(egg(['config_from' => $none, 'config_stop' => null]));
        $this->fail('The egg was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe(['config_stop']);
    }

    expect($eggs->create(egg(['config_from' => $none]))->config_from)->toBeNull();
})->with([0, '0']);
