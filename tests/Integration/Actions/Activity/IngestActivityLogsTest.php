<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Activity\IngestActivityLogsTest;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Activity\IngestsActivityLogs;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** An event Wings reports for the server, as it sends it. */
function reported(Server $server, array $overrides = []): array
{
    return [...[
        'server' => $server->uuid,
        'event' => 'server:console.command',
        'metadata' => ['command' => 'say hello'],
        'timestamp' => CarbonImmutable::now()->subMinute()->toRfc3339String(),
    ], ...$overrides];
}

/**
 * Activity logged against the server.
 *
 * @return Builder<ActivityLog>
 */
function logged(Server $server): Builder
{
    return ActivityLog::query()->whereHas('subjects', fn (Builder $query) => $query->where('subject_id', $server->id)->where('subject_type', $server->getMorphClass()));
}

test('activity ingested without the http api meets the same rules', function (array $overrides, string $field): void {
    $server = $this->createServerModel();
    $event = reported($server, $overrides);

    try {
        $this->app->make(IngestsActivityLogs::class)->ingest($server->node, ['data' => [reported($server), array_filter($event, fn (mixed $value): bool => $value !== 'unset')]]);
        $this->fail('The activity was ingested.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect(logged($server)->exists())->toBeFalse();
})->with([
    'a server that is not a uuid' => [['server' => 'not-a-uuid'], 'data.1.server'],
    'a missing event' => [['event' => 'unset'], 'data.1.event'],
    'an event that is not a string' => [['event' => ['server:power.start']], 'data.1.event'],
    'metadata left out' => [['metadata' => 'unset'], 'data.1.metadata'],
    'metadata that is not an object' => [['metadata' => 'command'], 'data.1.metadata'],
    'a user that is not a uuid' => [['user' => 'someone'], 'data.1.user'],
    'an ip that is not an ip' => [['ip' => '999.0.0.1'], 'data.1.ip'],
    'a missing timestamp' => [['timestamp' => 'unset'], 'data.1.timestamp'],
]);

test('a report without events, or with an event that is not an object, is refused', function (array $input, string $field): void {
    $server = $this->createServerModel();

    try {
        $this->app->make(IngestsActivityLogs::class)->ingest($server->node, $input);
        $this->fail('The activity was ingested.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toContain($field);
    }
})->with([
    'no events' => [[], 'data'],
    'events that are not a list' => [['data' => 'server:power.start'], 'data'],
    'an event that is not an object' => [['data' => ['server:power.start']], 'data.0'],
]);

test('events are recorded with their actor, null metadata is empty, and other nodes\' servers are skipped', function (): void {
    $server = $this->createServerModel();
    $other = $this->createServerModel();
    $user = User::factory()->create();

    $this->app->make(IngestsActivityLogs::class)->ingest($server->node, ['data' => [
        reported($server, ['user' => $user->uuid, 'ip' => '10.0.0.5']),
        reported($server, ['event' => 'server:power.start', 'metadata' => null]),
        reported($other, ['event' => 'server:power.stop']),
    ]]);

    $command = logged($server)->where('event', 'server:console.command')->sole();
    expect($command->actor_id)->toBe($user->id)
        ->and($command->ip)->toBe('10.0.0.5')
        ->and($command->properties->all())->toBe(['command' => 'say hello']);

    $start = logged($server)->where('event', 'server:power.start')->sole();
    expect($start->actor_id)->toBeNull()
        ->and($start->ip)->toBe('127.0.0.1')
        ->and($start->properties->all())->toBe([]);

    // The other server runs on another node, so the node cannot report activity for it.
    expect(logged($other)->exists())->toBeFalse();
});
