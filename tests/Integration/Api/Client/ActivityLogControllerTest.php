<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\ActivityLogControllerTest;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\User;

uses(\Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase::class);
test('account activity is returned', function () {
    /** @var User $user */
    $user = User::factory()->create();
    Activity::event('user:account.email-changed')->actor($user)->subject($user)->log();
    // Activity for someone else must never be visible on this account.
    Activity::event('user:account.email-changed')->actor($other = User::factory()->create())->subject($other)->log();
    $this->actingAs($user)->getJson('/api/client/account/activity')->assertOk()->assertJsonPath('object', 'list')->assertJsonCount(1, 'data')->assertJsonPath('data.0.object', 'activity_log')->assertJsonPath('data.0.attributes.event', 'user:account.email-changed');
});
test('account activity includes the logged properties', function () {
    /** @var User $user */
    $user = User::factory()->create();
    Activity::event('user:account.email-changed')->actor($user)->subject($user)
        ->property('old', 'old@example.com')
        ->property(['new' => 'new@example.com'])
        ->log();

    $this->actingAs($user)->getJson('/api/client/account/activity')->assertOk()
        ->assertJsonPath('data.0.attributes.properties.old', 'old@example.com')
        ->assertJsonPath('data.0.attributes.properties.new', 'new@example.com');
});
test('account activity can be filtered by event', function () {
    /** @var User $user */
    $user = User::factory()->create();
    Activity::event('user:account.email-changed')->actor($user)->subject($user)->log();
    Activity::event('user:account.password-changed')->actor($user)->subject($user)->log();
    $this->actingAs($user)->getJson('/api/client/account/activity?filter[event]=password-changed')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.event', 'user:account.password-changed');
});
test('account activity requires authentication', function () {
    $this->getJson('/api/client/account/activity')->assertUnauthorized();
});
test('server activity is returned', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ActivityRead->value]);
    Activity::event('server:power.start')->actor($user)->subject($server)->log();
    // Activity on another server must not leak into this listing.
    Activity::event('server:power.start')->actor($user)->subject($this->createServerModel())->log();
    $this->actingAs($user)->getJson($this->link($server, '/activity'))->assertOk()->assertJsonPath('object', 'list')->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.event', 'server:power.start');
});
test('server activity requires permission', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ControlConsole->value]);
    $this->getJson($this->link($server, '/activity'))->assertUnauthorized();
    $this->actingAs($user)->getJson($this->link($server, '/activity'))->assertForbidden();
});
test('the reserved ip property is hidden from other viewers while other addresses are not', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ActivityRead->value]);
    $other = User::factory()->create();
    Activity::event('server:power.start')
        ->actor($other)
        ->subject($server)
        ->property(['ip' => '198.51.100.7', 'address' => '10.0.0.1'])
        ->log();

    $properties = $this->actingAs($user)->getJson($this->link($server, '/activity'))->assertOk()->json('data.0.attributes.properties');

    expect($properties['ip'])->toBe('[hidden]');
    expect($properties['address'])->toBe('10.0.0.1');
});
test('a failed log in reveals the attempt address to the account it targeted', function () {
    $user = User::factory()->create(['root_admin' => false]);
    Activity::event('auth:fail')->anonymous()->subject($user)->withRequestMetadata()->log();

    $row = $this->actingAs($user)->getJson('/api/client/account/activity')->assertOk()->json('data.0.attributes');

    expect($row['event'])->toBe('auth:fail');
    expect($row['ip'])->toBe('127.0.0.1');
    expect($row['properties']['ip'])->toBe('127.0.0.1');
});
test('an entry with an actor never reveals that actor address to its subject', function () {
    $owner = User::factory()->create(['root_admin' => false]);
    $subuser = User::factory()->create(['root_admin' => false]);
    Activity::event('server:subuser.create')->actor($owner)->subject($subuser)->withRequestMetadata()->log();

    $row = $this->actingAs($subuser)->getJson('/api/client/account/activity')->assertOk()->json('data.0.attributes');

    expect($row['event'])->toBe('server:subuser.create');
    expect($row['ip'])->toBeNull();
    expect($row['properties']['ip'])->toBe('[hidden]');
});
test('an entry whose actor was deleted never reveals that actor address to its subject', function () {
    $owner = User::factory()->create(['root_admin' => false]);
    $subuser = User::factory()->create(['root_admin' => false]);
    Activity::event('server:subuser.create')->actor($owner)->subject($subuser)->withRequestMetadata()->log();
    User::query()->whereKey($owner->id)->delete();

    $row = $this->actingAs($subuser)->getJson('/api/client/account/activity')->assertOk()->json('data.0.attributes');

    expect($row['event'])->toBe('server:subuser.create');
    expect($row['ip'])->toBeNull();
    expect($row['properties']['ip'])->toBe('[hidden]');
});
test('an entry on a server whose actor was deleted does not reveal that actor address', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ActivityRead->value]);
    $other = User::factory()->create(['root_admin' => false]);
    Activity::event('server:power.start')->actor($other)->subject($server)->subject($user)->withRequestMetadata()->log();
    User::query()->whereKey($other->id)->delete();

    $row = $this->actingAs($user)->getJson($this->link($server, '/activity'))->assertOk()->json('data.0.attributes');

    expect($row['ip'])->toBeNull();
    expect($row['properties']['ip'])->toBe('[hidden]');
});
test('an administrator sees the same address in both fields', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ActivityRead->value]);
    $user->update(['root_admin' => true]);
    Activity::event('server:power.start')->actor(User::factory()->create())->subject($server)->withRequestMetadata()->log();

    $row = $this->actingAs($user)->getJson($this->link($server, '/activity'))->assertOk()->json('data.0.attributes');

    expect($row['ip'])->toBe('127.0.0.1');
    expect($row['properties']['ip'])->toBe('127.0.0.1');
});
test('entries are identified by the log id rather than the subject pivot id', function () {
    $user = User::factory()->create(['root_admin' => false]);
    $log = Activity::event('user:account.password-changed')->actor($user)->subject($user)->log();

    $row = $this->actingAs($user)->getJson('/api/client/account/activity')->assertOk()->json('data.0.attributes');

    expect($row['id'])->toBe(sha1((string) $log->id));
    expect(ActivityLog::query()->whereKey($log->id)->exists())->toBeTrue();
});
test('account activity can be filtered by the user that performed it', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $admin = User::factory()->create(['username' => 'an-admin']);
    Activity::event('user:account.password-changed')->actor($user)->subject($user)->log();
    Activity::event('user:account.email-changed')->actor($admin)->subject($user)->log();

    $events = collect($this->actingAs($user)->getJson('/api/client/account/activity?filter[user]=an-admin')->assertOk()->json('data'))->pluck('attributes.event');

    expect($events->all())->toBe(['user:account.email-changed']);
});
test('server activity can be filtered by the user that performed it', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ActivityRead->value]);
    $other = User::factory()->create(['username' => 'other-actor']);
    Activity::event('server:power.start')->actor($user)->subject($server)->log();
    Activity::event('server:power.stop')->actor($other)->subject($server)->log();

    $events = collect($this->actingAs($user)->getJson($this->link($server, '/activity?filter[user]=other-actor'))->assertOk()->json('data'))->pluck('attributes.event');

    expect($events->all())->toBe(['server:power.stop']);
});
test('account filter options are scoped to that account', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $admin = User::factory()->create(['username' => 'an-admin']);
    $stranger = User::factory()->create(['username' => 'stranger']);
    Activity::event('user:account.password-changed')->actor($user)->subject($user)->log();
    Activity::event('user:account.email-changed')->actor($admin)->subject($user)->log();
    Activity::event('user:api-key.create')->actor($stranger)->subject($stranger)->log();

    $data = $this->actingAs($user)->getJson('/api/client/account/activity/filters')->assertOk()->json('data');

    expect($data['events'])->toBe(['user:account.email-changed', 'user:account.password-changed']);
    expect(collect($data['users'])->pluck('username')->all())->toContain('an-admin');
    expect(collect($data['users'])->pluck('username')->all())->not->toContain('stranger');
});
test('server filter options are scoped to that server and need permission', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ActivityRead->value]);
    $other = User::factory()->create(['username' => 'other-actor']);
    Activity::event('server:power.start')->actor($user)->subject($server)->log();
    Activity::event('server:power.stop')->actor($other)->subject($server)->log();
    Activity::event('server:power.kill')->actor($other)->subject($this->createServerModel())->log();

    $data = $this->actingAs($user)->getJson($this->link($server, '/activity/filters'))->assertOk()->json('data');

    expect($data['events'])->toBe(['server:power.start', 'server:power.stop']);
    expect(collect($data['users'])->pluck('username')->all())->toContain('other-actor');

    [$denied, $server] = $this->generateTestAccount([Permissions::ControlConsole->value]);
    $this->actingAs($denied)->getJson($this->link($server, '/activity/filters'))->assertForbidden();
});
test('filtering by user id is exact on the client feeds', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ActivityRead->value]);
    $other = User::factory()->create(['username' => 'other-actor']);
    Activity::event('server:power.start')->actor($user)->subject($server)->log();
    Activity::event('server:power.stop')->actor($other)->subject($server)->log();

    $events = collect($this->actingAs($user)->getJson($this->link($server, '/activity?filter[user_id]='.$other->id))->assertOk()->json('data'))->pluck('attributes.event');

    expect($events->all())->toBe(['server:power.stop']);
});
test('an entry is only flagged as api when it points at a real key', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ActivityRead->value]);
    $log = Activity::event('server:power.start')->actor($user)->subject($server)->log();
    $log->forceFill(['api_key_id' => 0])->save();

    $row = $this->actingAs($user)->getJson($this->link($server, '/activity'))->assertOk()->json('data.0.attributes');

    expect($row['is_api'])->toBeFalse();
});

test('malformed activity user filters return 422 on the client feeds', function (string $feed) {
    [$user, $server] = $this->generateTestAccount([Permissions::ActivityRead->value]);
    $endpoint = $feed === 'account' ? '/api/client/account/activity' : $this->link($server, '/activity');

    $this->actingAs($user)->getJson($endpoint.'?'.http_build_query(['filter' => ['user' => ['nested' => 'value']]]))
        ->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'filter.user');
})->with(['account', 'server']);

test('server filter options respect hidden administrator activity', function () {
    config()->set('activity.hide_admin_activity', true);
    [$user, $server] = $this->generateTestAccount([Permissions::ActivityRead->value]);
    $admin = User::factory()->create(['root_admin' => true]);
    Activity::event('server:power.start')->actor($user)->subject($server)->log();
    Activity::event('server:power.kill')->actor($admin)->subject($server)->log();

    $response = $this->actingAs($user)->getJson($this->link($server, '/activity/filters'))->assertOk();

    $response->assertJsonPath('data.events', ['server:power.start']);
    $response->assertJsonCount(1, 'data.users');
    $response->assertJsonPath('data.users.0.id', $user->id);
});

test('disabled events and their actors are excluded from server filter options', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ActivityRead->value]);
    Activity::event(ActivityLog::DISABLED_EVENTS[0])->actor($user)->subject($server)->log();

    $this->actingAs($user)->getJson($this->link($server, '/activity/filters'))->assertOk()
        ->assertJsonPath('data.events', [])->assertJsonPath('data.users', []);
});
