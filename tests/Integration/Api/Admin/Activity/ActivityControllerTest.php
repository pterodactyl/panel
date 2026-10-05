<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Activity\ActivityControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);

test('activity is returned newest first', function () {
    $server = $this->createServerModel();
    Activity::event('admin:server.build')->actor($this->getAdminUser())->subject($server)->property('name', $server->name)->log();
    Activity::event('admin:server.reinstall')->actor($this->getAdminUser())->subject($server)->property('name', $server->name)->log();

    $response = $this->getJson(route('api.admin.activity'))->assertStatus(Response::HTTP_OK);

    $response->assertJsonPath('object', 'list');
    $response->assertJsonPath('data.0.object', 'activity_log');
    $response->assertJsonPath('data.0.attributes.event', 'admin:server.reinstall');
    $response->assertJsonPath('data.1.attributes.event', 'admin:server.build');
});
test('administrative activity spans every server rather than one', function () {
    $first = $this->createServerModel();
    $second = $this->createServerModel();
    Activity::event('admin:server.build')->actor($this->getAdminUser())->subject($first)->property('name', $first->name)->log();
    Activity::event('admin:server.suspend')->actor($this->getAdminUser())->subject($second)->property('name', $second->name)->log();

    $events = collect($this->getJson(route('api.admin.activity'))->assertOk()->json('data'))->pluck('attributes.event');

    expect($events)->toContain('admin:server.build', 'admin:server.suspend');
});
test('customer activity is excluded from the administrative log', function () {
    $server = $this->createServerModel();
    Activity::event('admin:server.build')->actor($this->getAdminUser())->subject($server)->property('name', $server->name)->log();
    Activity::event('server:power.start')->actor($this->getAdminUser())->subject($server)->log();
    Activity::event('server:file.write')->actor($this->getAdminUser())->subject($server)->property('file', '/a.txt')->log();
    Activity::event('user:account.password-changed')->actor($this->getAdminUser())->subject($this->getAdminUser())->log();
    Activity::event('auth:success')->actor($this->getAdminUser())->subject($this->getAdminUser())->log();

    $events = collect($this->getJson(route('api.admin.activity'))->assertOk()->json('data'))->pluck('attributes.event');

    expect($events)->toContain('admin:server.build');
    expect($events)->not->toContain('server:power.start');
    expect($events)->not->toContain('server:file.write');
    expect($events)->not->toContain('user:account.password-changed');
    expect($events)->not->toContain('auth:success');
});
test('activity can be filtered by event and includes the actor', function () {
    $server = $this->createServerModel();
    Activity::event('admin:server.build')->actor($this->getAdminUser())->subject($server)->property('name', $server->name)->log();
    Activity::event('admin:server.suspend')->actor($this->getAdminUser())->subject($server)->property('name', $server->name)->log();

    $response = $this->getJson(route('api.admin.activity', ['filter' => ['event' => 'suspend'], 'include' => 'actor']))->assertOk();

    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.attributes.event', 'admin:server.suspend');
    $response->assertJsonPath('data.0.attributes.relationships.actor.attributes.email', $this->getAdminUser()->email);
});
test('the originating address is exposed to administrators', function () {
    $server = $this->createServerModel();
    Activity::event('admin:server.build')->actor($this->getAdminUser())->subject($server)->property('name', $server->name)->withRequestMetadata()->log();

    $row = $this->getJson(route('api.admin.activity'))->assertOk()->json('data.0.attributes');

    expect($row['ip'])->toBe('127.0.0.1');
    expect($row['properties']['ip'])->toBe('127.0.0.1');
});
test('disabled events are never listed', function () {
    $server = $this->createServerModel();
    Activity::event(ActivityLog::DISABLED_EVENTS[0])->actor($this->getAdminUser())->subject($server)->log();

    $events = collect($this->getJson(route('api.admin.activity'))->assertOk()->json('data'))->pluck('attributes.event');

    expect($events)->not->toContain(ActivityLog::DISABLED_EVENTS[0]);
});
test('the event filter cannot reach outside the administrative namespace', function () {
    $server = $this->createServerModel();
    Activity::event('server:power.start')->actor($this->getAdminUser())->subject($server)->log();

    $response = $this->getJson(route('api.admin.activity', ['filter' => ['event' => 'server:power.start']]))->assertOk();

    $response->assertJsonCount(0, 'data');
});
test('endpoint requires an authenticated administrator', function () {
    $this->app->get('auth')->forgetGuards();
    $this->getJson(route('api.admin.activity'))->assertUnauthorized();

    $this->actingAs(User::factory()->create(['root_admin' => false]));
    $this->getJson(route('api.admin.activity'))->assertForbidden();
});
test('activity can be filtered by the user that performed it', function () {
    $server = $this->createServerModel();
    $other = User::factory()->create(['username' => 'someone-else', 'email' => 'else@example.com']);
    Activity::event('admin:server.build')->actor($this->getAdminUser())->subject($server)->property('name', $server->name)->log();
    Activity::event('admin:server.suspend')->actor($other)->subject($server)->property('name', $server->name)->log();

    $byUsername = collect($this->getJson(route('api.admin.activity', ['filter' => ['user' => 'someone-else']]))->assertOk()->json('data'))->pluck('attributes.event');
    expect($byUsername->all())->toBe(['admin:server.suspend']);

    $byEmail = collect($this->getJson(route('api.admin.activity', ['filter' => ['user' => 'else@example.com']]))->assertOk()->json('data'))->pluck('attributes.event');
    expect($byEmail->all())->toBe(['admin:server.suspend']);
});
test('the user filter combines with the event filter', function () {
    $server = $this->createServerModel();
    $other = User::factory()->create(['username' => 'someone-else']);
    Activity::event('admin:server.build')->actor($other)->subject($server)->property('name', $server->name)->log();
    Activity::event('admin:server.suspend')->actor($other)->subject($server)->property('name', $server->name)->log();
    Activity::event('admin:server.suspend')->actor($this->getAdminUser())->subject($server)->property('name', $server->name)->log();

    $response = $this->getJson(route('api.admin.activity', ['filter' => ['user' => 'someone-else', 'event' => 'suspend']]))->assertOk();

    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.attributes.event', 'admin:server.suspend');
});
test('an entry without a user actor is excluded by the user filter', function () {
    $server = $this->createServerModel();
    Activity::event('admin:server.build')->anonymous()->subject($server)->property('name', $server->name)->log();

    $this->getJson(route('api.admin.activity', ['filter' => ['user' => 'anything']]))->assertOk()->assertJsonCount(0, 'data');
});
test('filtering by user id matches only that user', function () {
    $server = $this->createServerModel();
    $dan = User::factory()->create(['username' => 'dan']);
    $danny = User::factory()->create(['username' => 'danny']);
    Activity::event('admin:server.build')->actor($dan)->subject($server)->property('name', $server->name)->log();
    Activity::event('admin:server.suspend')->actor($danny)->subject($server)->property('name', $server->name)->log();

    $byText = $this->getJson(route('api.admin.activity', ['filter' => ['user' => 'dan']]))->assertOk();
    expect($byText->json('data'))->toHaveCount(2);

    $byId = $this->getJson(route('api.admin.activity', ['filter' => ['user_id' => $dan->id]]))->assertOk();
    expect($byId->json('data'))->toHaveCount(1);
    $byId->assertJsonPath('data.0.attributes.event', 'admin:server.build');
});
test('filter options list the events and users present in the log', function () {
    $server = $this->createServerModel();
    $other = User::factory()->create(['username' => 'zz-last']);
    Activity::event('admin:server.build')->actor($this->getAdminUser())->subject($server)->property('name', $server->name)->log();
    Activity::event('admin:server.suspend')->actor($other)->subject($server)->property('name', $server->name)->log();
    Activity::event('server:power.start')->actor($other)->subject($server)->log();

    $data = $this->getJson(route('api.admin.activity.filters'))->assertOk()->json('data');

    expect($data['events'])->toBe(['admin:server.build', 'admin:server.suspend']);
    expect(collect($data['users'])->pluck('username')->all())->toContain('zz-last');
    expect(collect($data['users'])->pluck('id')->all())->toContain($other->id);
});
test('filter options never leak customer activity into the admin log', function () {
    $server = $this->createServerModel();
    Activity::event('server:power.start')->actor($this->getAdminUser())->subject($server)->log();

    $data = $this->getJson(route('api.admin.activity.filters'))->assertOk()->json('data');

    expect($data['events'])->toBe([]);
    expect($data['users'])->toBe([]);
});
test('filter options require administrator access', function () {
    $this->actingAs(User::factory()->create(['root_admin' => false]));

    $this->getJson(route('api.admin.activity.filters'))->assertForbidden();
});
test('the exact event filter does not match longer event names', function () {
    $node = $this->createServerModel()->node;
    Activity::event('admin:node-allocation.delete')->actor($this->getAdminUser())->subject($node)->property('address', '10.0.0.1')->property('port', 25565)->log();
    Activity::event('admin:node-allocation.delete-block')->actor($this->getAdminUser())->subject($node)->property('address', '10.0.0.1')->log();

    expect($this->getJson(route('api.admin.activity', ['filter' => ['event' => 'admin:node-allocation.delete']]))->assertOk()->json('data'))->toHaveCount(2);

    $exact = $this->getJson(route('api.admin.activity', ['filter' => ['event_name' => 'admin:node-allocation.delete']]))->assertOk();
    expect($exact->json('data'))->toHaveCount(1);
    $exact->assertJsonPath('data.0.attributes.event', 'admin:node-allocation.delete');
});

test('malformed activity user filters return 422', function () {
    $this->getJson(route('api.admin.activity', ['filter' => ['user' => ['nested' => 'value']]]))
        ->assertUnprocessable()->assertJsonPath('errors.0.meta.source_field', 'filter.user');
});

test('actor filters retain commas in email addresses', function () {
    $actor = User::factory()->create(['email' => 'comma,actor@example.com']);
    Activity::event('admin:user.create')->actor($actor)->subject($actor)->property('username', $actor->username)->log();

    $this->getJson(route('api.admin.activity', ['filter' => ['user' => 'comma,actor@example.com']]))
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.event', 'admin:user.create');
});
