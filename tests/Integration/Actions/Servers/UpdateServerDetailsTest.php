<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Servers\UpdateServerDetailsTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Pterodactyl\Contracts\Servers\UpdatesServerDetails;
use Pterodactyl\Jobs\RevokeSftpAccessJob;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, mixed> */
function details(Server $server, array $overrides = []): array
{
    return [...[
        'external_id' => 'ext-details',
        'name' => 'Updated Server',
        'owner_id' => $server->owner_id,
        'description' => 'An updated description.',
    ], ...$overrides];
}

test('details updated without the http api meet the same rules', function (array $input, string $field): void {
    $server = $this->createServerModel()->refresh();
    $before = $server->getAttributes();

    try {
        $this->app->make(UpdatesServerDetails::class)->update($server, details($server, $input));
        $this->fail('The details were updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($server->fresh()->getAttributes())->toBe($before);
})->with([
    'a missing name' => [['name' => null], 'name'],
    'a name that is too long' => [['name' => str_repeat('a', 192)], 'name'],
    'a missing owner' => [['owner_id' => null], 'owner_id'],
    'an owner id that is not an integer' => [['owner_id' => '10.5'], 'owner_id'],
    'an owner that does not exist' => [['owner_id' => 0], 'owner_id'],
    'an external id that is too long' => [['external_id' => str_repeat('a', 192)], 'external_id'],
    'an external id that is not a string' => [['external_id' => ['ext']], 'external_id'],
    'a description that is not a string' => [['description' => ['text']], 'description'],
]);

test('an external id another server holds is refused, but the server keeps its own', function (): void {
    $this->createServerModel(['external_id' => 'ext-taken']);
    $server = $this->createServerModel(['external_id' => 'ext-own']);

    try {
        $this->app->make(UpdatesServerDetails::class)->update($server, details($server, ['external_id' => 'ext-taken']));
        $this->fail('The details were updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe(['external_id']);
    }

    expect($this->app->make(UpdatesServerDetails::class)->update($server, details($server, ['external_id' => 'ext-own']))->external_id)->toBe('ext-own');
});

test('every detail is replaced, so an external id and description left out are cleared', function (): void {
    $server = $this->createServerModel(['external_id' => 'ext-old', 'description' => 'Old description.']);

    $updated = $this->app->make(UpdatesServerDetails::class)->update($server, ['name' => 'Renamed', 'owner_id' => $server->owner_id]);

    expect($updated->name)->toBe('Renamed')
        ->and($updated->owner_id)->toBe($server->owner_id)
        ->and($updated->external_id)->toBeNull()
        ->and($updated->description)->toBe('');

    $updated = $this->app->make(UpdatesServerDetails::class)->update($server, details($server, ['external_id' => '', 'description' => null]));

    expect($updated->external_id)->toBeNull()
        ->and($updated->description)->toBe('');
});

test('values are coerced the way laravel validates them, and a new owner loses sftp access', function (): void {
    Bus::fake();
    $server = $this->createServerModel();
    $previous = $server->user;
    $owner = User::factory()->create();

    $updated = $this->app->make(UpdatesServerDetails::class)->update($server, details($server, ['owner_id' => (string) $owner->id]));

    expect($updated->owner_id)->toBe($owner->id)
        ->and($updated->external_id)->toBe('ext-details')
        ->and($updated->description)->toBe('An updated description.');
    Bus::assertDispatched(RevokeSftpAccessJob::class, fn (RevokeSftpAccessJob $job): bool => $job->user === $previous->uuid);
});

test('an unchanged owner keeps sftp access', function (): void {
    Bus::fake();
    $server = $this->createServerModel();

    $this->app->make(UpdatesServerDetails::class)->update($server, details($server, ['owner_id' => (string) $server->owner_id]));

    Bus::assertNotDispatched(RevokeSftpAccessJob::class);
});

test('a rename made without the http api is logged on the server, and an unchanged description is not', function (): void {
    $server = $this->createServerModel(['name' => 'Old Name', 'description' => 'Same.']);

    $this->app->make(UpdatesServerDetails::class)->update($server, details($server, ['name' => 'New Name', 'description' => 'Same.']));

    expect($this->assertActivityOn('server:settings.rename', $server)->properties->all())->toBe(['old' => 'Old Name', 'new' => 'New Name'])
        ->and($server->activity()->where('event', 'server:settings.description')->exists())->toBeFalse();
});

test('extension values must come from a request that validated them', function (): void {
    $server = $this->createServerModel()->refresh();
    $before = $server->getAttributes();

    expect(fn () => $this->app->make(UpdatesServerDetails::class)->update($server, details($server, ['extensions' => ['billing' => ['plan' => 'pro']]])))
        ->toThrow(InvalidArgumentException::class, 'must come from a request that validated them');

    expect($server->fresh()->getAttributes())->toBe($before);
});
