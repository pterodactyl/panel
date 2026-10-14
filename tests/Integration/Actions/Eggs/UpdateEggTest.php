<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Eggs\UpdateEggTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Eggs\UpdatesEggs;
use Pterodactyl\Models\Egg;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, mixed> */
function egg(array $overrides = []): array
{
    return [...[
        'name' => 'Updated Egg',
        'docker_images' => 'Java 21|ghcr.io/pterodactyl/yolks:java_21',
        'startup' => 'java -jar updated.jar',
        'config_stop' => '^C',
        'config_startup' => '{}',
        'config_logs' => '{}',
        'config_files' => '{}',
    ], ...$overrides];
}

test('eggs updated without the http api meet the same rules', function (array $input, string $field): void {
    $egg = Egg::factory()->create()->refresh();
    $before = $egg->getAttributes();

    try {
        $this->app->make(UpdatesEggs::class)->update($egg, egg($input));
        $this->fail('The egg was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($egg->fresh()->getAttributes())->toBe($before);
})->with([
    'a missing name' => [['name' => ''], 'name'],
    'a missing docker image list' => [['docker_images' => null], 'docker_images'],
    'an invalid docker image' => [['docker_images' => 'ghcr.io/x;rm -rf /'], 'docker_images'],
    'a feature that is not a string' => [['features' => [['eula']]], 'features.0'],
    'an outgoing ip flag that is not a boolean' => [['force_outgoing_ip' => 'sometimes'], 'force_outgoing_ip'],
    'a parent egg that does not exist' => [['config_from' => 999999], 'config_from'],
    'logs config that is not json' => [['config_logs' => 'logs'], 'config_logs'],
    'no logs config and no parent egg' => [['config_logs' => null], 'config_logs'],
]);

test('an update replaces the egg fields and keeps the config it is not given', function (): void {
    $parent = Egg::factory()->create();
    $egg = Egg::factory()->create(['force_outgoing_ip' => true, 'features' => ['eula'], 'file_denylist' => ['secret.txt']])->refresh();
    $config = $egg->only(['config_startup', 'config_logs', 'config_files']);

    $this->app->make(UpdatesEggs::class)->update($egg, [
        'name' => 'Updated Egg',
        'docker_images' => 'Java 21|ghcr.io/pterodactyl/yolks:java_21',
        'startup' => 'java -jar updated.jar',
        'config_from' => $parent->id,
        'config_stop' => '',
        'file_denylist' => ['other.txt'],
    ]);

    $egg->refresh();
    expect($egg->name)->toBe('Updated Egg')
        ->and($egg->docker_images)->toBe(['Java 21' => 'ghcr.io/pterodactyl/yolks:java_21'])
        ->and($egg->startup)->toBe('java -jar updated.jar')
        ->and($egg->config_from)->toBe($parent->id)
        ->and($egg->config_stop)->toBeNull()
        ->and($egg->only(['config_startup', 'config_logs', 'config_files']))->toBe($config)
        // Left out, the description, outgoing IP flag and features are cleared.
        ->and($egg->description)->toBeNull()
        ->and($egg->force_outgoing_ip)->toBeFalse()
        ->and($egg->features)->toBe([])
        // The denylist cannot be changed through an update yet.
        ->and($egg->file_denylist)->toBe(['secret.txt']);
});

test('values are coerced the way laravel validates them', function (int|string $flag, bool $expected): void {
    $parent = Egg::factory()->create();
    $egg = Egg::factory()->create();

    $this->app->make(UpdatesEggs::class)->update($egg, egg(['force_outgoing_ip' => $flag, 'config_from' => (string) $parent->id]));

    $egg->refresh();
    expect($egg->force_outgoing_ip)->toBe($expected)
        ->and($egg->config_from)->toBe($parent->id);

    $this->app->make(UpdatesEggs::class)->update($egg, egg(['config_from' => '0']));
    expect($egg->refresh()->config_from)->toBeNull();
})->with([[1, true], ['1', true], [0, false], ['0', false]]);
