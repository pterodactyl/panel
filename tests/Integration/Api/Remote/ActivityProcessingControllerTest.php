<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Remote\ActivityProcessingControllerTest;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

use function pterodactylTestCase;

uses(IntegrationTestCase::class);
uses(DatabaseTransactions::class);
beforeEach(function () {
    [$user, $server] = $this->generateTestAccount();
    $this->user = $user;
    $this->server = $server;
    setAuthorization($server->node);
});
test('events are recorded against the server and actor', function () {
    $timestamp = CarbonImmutable::now()->subMinute();
    $this->postJson('/api/remote/activity', ['data' => [['user' => $this->user->uuid, 'server' => $this->server->uuid, 'event' => 'server:console.command', 'metadata' => ['command' => 'say hello'], 'ip' => '192.168.1.1', 'timestamp' => $timestamp->toRfc3339String()]]])->assertSuccessful();
    $log = logsFor($this->server)->where('event', 'server:console.command')->sole();
    expect($log->ip)->toBe('192.168.1.1');
    expect($log->actor_id)->toBe($this->user->id);
    expect($log->actor_type)->toBe($this->user->getMorphClass());
    expect($log->properties->all())->toBe(['command' => 'say hello']);
    expect($log->timestamp->utc()->toDateTimeString())->toBe($timestamp->toDateTimeString());
    $subject = $log->subjects->sole();
    expect($subject->subject_id)->toBe($this->server->id);
    expect($subject->subject_type)->toBe($this->server->getMorphClass());
});
test('events without a user are recorded with no actor', function () {
    $this->postJson('/api/remote/activity', ['data' => [['server' => $this->server->uuid, 'event' => 'server:crashed', 'metadata' => null, 'timestamp' => CarbonImmutable::now()->toRfc3339String()]]])->assertSuccessful();
    $log = logsFor($this->server)->where('event', 'server:crashed')->sole();
    expect($log->actor_id)->toBeNull();
    expect($log->actor_type)->toBeNull();
    expect($log->ip)->toBe('127.0.0.1');
});
test('events for other nodes and foreign namespaces are discarded', function () {
    $other = $this->createServerModel();
    $this->assertNotSame($this->server->node_id, $other->node_id);
    $this->postJson('/api/remote/activity', ['data' => [['user' => $this->user->uuid, 'server' => $other->uuid, 'event' => 'server:console.command', 'metadata' => [], 'timestamp' => CarbonImmutable::now()->toRfc3339String()], ['user' => $this->user->uuid, 'server' => $this->server->uuid, 'event' => 'user:api-key.create', 'metadata' => [], 'timestamp' => CarbonImmutable::now()->toRfc3339String()]]])->assertSuccessful();
    expect(logsFor($this->server)->count())->toBe(0);
    expect(logsFor($other)->count())->toBe(0);
});
test('unparsable timestamps do not fail the request', function () {
    $this->postJson('/api/remote/activity', ['data' => [['user' => $this->user->uuid, 'server' => $this->server->uuid, 'event' => 'server:console.command', 'metadata' => [], 'timestamp' => 'not a timestamp']]])->assertSuccessful();
    $log = logsFor($this->server)->where('event', 'server:console.command')->sole();
    expect($log->properties->get('original_timestamp'))->toBe('not a timestamp');
});
/**
 * Activity logged against a specific server. The suite shares one database
 * across test classes, so assertions have to be scoped to this test's own
 * server rather than counting every row in the table.
 *
 * @return Builder<ActivityLog>
 */
function logsFor(Server $server): Builder
{
    return ActivityLog::query()->whereHas('subjects', fn (Builder $query) => $query->where('subject_id', $server->id)->where('subject_type', $server->getMorphClass()));
}
function setAuthorization(Node $node): void
{
    (function () use ($node) {
        $this->withHeader('Authorization', 'Bearer '.$node->daemon_token_id.'.'.decrypt($node->daemon_token));
    })->call(pterodactylTestCase());
}
