<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Fixtures\ClientApiFixturesTest;

use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Integration\Api\Fixtures\ApiFixtureWorld as World;

uses(ClientApiIntegrationTestCase::class);

beforeEach(function (): void {
    World::build();
});

afterEach(function (): void {
    World::tearDown();
});

const CLIENT = '/api/client';

const SERVER = CLIENT.'/servers/'.World::SURVIVAL;

const LAB = CLIENT.'/servers/'.World::LAB;

/** Requests act as the server owner unless the scenario says otherwise. */
function clientScenarios(): array
{
    $cron = ['minute' => '0', 'hour' => '*', 'day_of_week' => '*', 'month' => '*', 'day_of_month' => '*'];

    return [
        'client/servers.index' => ['requests' => [
            ...World::allIncludes(CLIENT, ['egg', 'subusers']),
            ...World::pages(CLIENT, CLIENT.'?filter[name]=none'),
            ['GET', CLIENT.'?per_page=1&page=1&filter[uuid]=5e7f&include=egg'],
            ['GET', CLIENT.'?type=owner&per_page=1&page=2&include=subusers'],
        ]],
        'client/servers.index.as-admin' => ['as' => World::ADMIN, 'requests' => [
            ['GET', CLIENT],
            ['GET', CLIENT.'?type=admin'],
            ['GET', CLIENT.'?type=admin-all&per_page=2&include=egg'],
            ['GET', CLIENT.'?type=admin-all&per_page=2&page=2'],
        ]],
        'client/servers.view' => ['requests' => World::eachInclude(SERVER, ['egg', 'subusers'])],
        'client/servers.as-full-subuser' => ['as' => World::HELPER, 'requests' => [
            ['GET', CLIENT.'?include=egg,subusers'],
            ['GET', SERVER.'?include=egg,subusers'],
            ['GET', SERVER.'/databases?include=password'],
            ['GET', SERVER.'/activity?include=actor'],
            ['GET', SERVER.'/network/allocations'],
            ['GET', SERVER.'/startup'],
            ['GET', SERVER.'/users'],
            ['GET', SERVER.'/resources'],
        ]],
        'client/servers.as-limited-subuser' => ['as' => World::LIMITED, 'requests' => [
            ['GET', CLIENT],
            ['GET', CLIENT.'?include=egg,subusers'],
            ['GET', SERVER],
            ['GET', SERVER.'?include=egg,subusers'],
            ['GET', SERVER.'/databases?include=password'],
            ['GET', SERVER.'/activity?include=actor'],
            ['GET', SERVER.'/backups'],
            ['GET', SERVER.'/schedules'],
        ]],
        // The administrator's own server has none of the owner's resources, so its lists are
        // the empty ones; its node leaves maintenance so they can be read.
        'client/servers.as-admin' => ['as' => World::ADMIN, 'setup' => fn () => Node::query()->whereKey(2)->update(['maintenance_mode' => false]), 'requests' => [
            ['GET', SERVER.'?include=egg,subusers'],
            ['GET', SERVER.'/activity?include=actor'],
            ['GET', SERVER.'/databases?include=password'],
            ['GET', LAB],
            ['GET', LAB.'/backups'],
            ['GET', LAB.'/schedules'],
            ['GET', LAB.'/users'],
            ['GET', LAB.'/network/allocations'],
            ['GET', LAB.'/startup'],
            ['GET', LAB.'/databases'],
            ['GET', LAB.'/activity'],
        ]],
        'client/account.view' => ['requests' => [['GET', CLIENT.'/account']]],
        'client/account.empty' => ['as' => World::SPARE, 'requests' => [
            ['GET', CLIENT],
            ['GET', CLIENT.'/account'],
            ['GET', CLIENT.'/account/activity'],
            ['GET', CLIENT.'/account/api-keys'],
            ['GET', CLIENT.'/account/ssh-keys'],
        ]],
        'client/account/activity.index' => ['requests' => [
            ...World::eachInclude(CLIENT.'/account/activity', ['actor']),
            ...World::pages(CLIENT.'/account/activity', CLIENT.'/account/activity?filter[event]=none'),
            ['GET', CLIENT.'/account/activity?per_page=1&page=2&sort=-timestamp&include=actor'],
        ]],
        'client/account/api-keys.index' => ['requests' => [['GET', CLIENT.'/account/api-keys']]],
        'client/account/api-keys.store' => ['requests' => [['POST', CLIENT.'/account/api-keys', ['description' => 'Created key', 'allowed_ips' => ['127.0.0.1', '192.0.2.0/24']]]]],
        'client/account/ssh-keys.index' => ['requests' => [['GET', CLIENT.'/account/ssh-keys']]],
        'client/account/ssh-keys.store' => ['requests' => [['POST', CLIENT.'/account/ssh-keys', ['name' => 'Workstation', 'public_key' => World::NEW_SSH_KEY]]]],
        'client/servers/activity.index' => ['requests' => [
            ...World::eachInclude(SERVER.'/activity', ['actor']),
            ...World::pages(SERVER.'/activity', SERVER.'/activity?filter[event]=none'),
            ['GET', SERVER.'/activity?per_page=2&page=2&sort=timestamp&filter[event]=server:&include=actor'],
        ]],
        'client/servers/backups.index' => ['requests' => [['GET', SERVER.'/backups'], ...World::pages(SERVER.'/backups')]],
        'client/servers/backups.view' => ['requests' => [
            ['GET', SERVER.'/backups/'.World::BACKUP_NIGHTLY],
            ['GET', SERVER.'/backups/'.World::BACKUP_FAILED],
            ['GET', SERVER.'/backups/'.World::BACKUP_RUNNING],
        ]],
        'client/servers/backups.store' => ['requests' => [['POST', SERVER.'/backups', ['name' => 'Manual backup', 'is_locked' => true, 'ignored' => "logs/\ncache/"]]]],
        'client/servers/backups.lock' => ['requests' => [['POST', SERVER.'/backups/'.World::BACKUP_FAILED.'/lock']]],
        'client/servers/databases.index' => ['requests' => World::eachInclude(SERVER.'/databases', ['password'])],
        'client/servers/databases.store' => ['redact' => ['password'], 'requests' => [['POST', SERVER.'/databases', ['database' => 'clientdb', 'remote' => '%']]]],
        'client/servers/databases.store.as-limited-subuser' => ['as' => World::LIMITED, 'redact' => ['password'], 'requests' => [['POST', SERVER.'/databases', ['database' => 'limiteddb', 'remote' => '%']]]],
        'client/servers/databases.rotate-password' => ['redact' => ['password'], 'requests' => [['POST', SERVER.'/databases/1/rotate-password']]],
        'client/servers/databases.rotate-password.as-limited-subuser' => ['as' => World::LIMITED, 'redact' => ['password'], 'requests' => [['POST', SERVER.'/databases/2/rotate-password']]],
        'client/servers/files.list' => ['requests' => [['GET', SERVER.'/files/list']]],
        'client/servers/files.compress' => ['requests' => [['POST', SERVER.'/files/compress', ['root' => '/', 'files' => ['plugins', 'server.properties']]]]],
        'client/servers/network/allocations.index' => ['requests' => [['GET', SERVER.'/network/allocations']]],
        'client/servers/network/allocations.store' => ['requests' => [['POST', SERVER.'/network/allocations']]],
        'client/servers/network/allocations.update' => ['requests' => [['POST', SERVER.'/network/allocations/2', ['notes' => 'Updated <notes> & "quotes"']]]],
        'client/servers/network/allocations.primary' => ['requests' => [['POST', SERVER.'/network/allocations/2/primary']]],
        'client/servers/resources.view' => ['requests' => [['GET', SERVER.'/resources']]],
        'client/servers/schedules.index' => ['requests' => [['GET', SERVER.'/schedules']]],
        'client/servers/schedules.view' => ['requests' => [['GET', SERVER.'/schedules/1'], ['GET', SERVER.'/schedules/2']]],
        'client/servers/schedules.store' => ['redact' => ['next_run_at'], 'requests' => [['POST', SERVER.'/schedules', ['name' => 'Hourly backup', 'is_active' => true, 'only_when_online' => false, ...$cron]]]],
        'client/servers/schedules.update' => ['redact' => ['next_run_at'], 'requests' => [['POST', SERVER.'/schedules/1', ['name' => 'Nightly restart (edited)', 'is_active' => false, ...$cron, 'minute' => '5', 'hour' => '3']]]],
        'client/servers/schedules/tasks.store' => ['requests' => [['POST', SERVER.'/schedules/1/tasks', ['action' => 'command', 'payload' => 'say hello', 'time_offset' => 10, 'sequence_id' => 1, 'continue_on_failure' => true]]]],
        'client/servers/schedules/tasks.update' => ['requests' => [['POST', SERVER.'/schedules/1/tasks/1', ['action' => 'command', 'payload' => 'say "edited"', 'time_offset' => 30]]]],
        'client/servers/startup.view' => ['requests' => [['GET', SERVER.'/startup']]],
        'client/servers/startup/variable.update' => ['requests' => [['PUT', SERVER.'/startup/variable', ['key' => 'SERVER_JARFILE', 'value' => 'custom.jar']]]],
        'client/servers/users.index' => ['requests' => [['GET', SERVER.'/users']]],
        'client/servers/users.view' => ['requests' => [
            ['GET', SERVER.'/users/'.World::HELPER_UUID],
            ['GET', SERVER.'/users/'.World::LIMITED_UUID.'?include=does-not-exist'],
        ]],
        'client/servers/users.store' => ['requests' => [['POST', SERVER.'/users', ['email' => 'new.subuser@example.test', 'permissions' => ['control.console', 'file.read']]]]],
        'client/servers/users.update' => ['requests' => [['POST', SERVER.'/users/'.World::HELPER_UUID, ['permissions' => ['control.console', 'control.start', 'control.stop']]]]],
    ];
}

test('client api responses match their fixtures', function (string $fixture): void {
    expect(World::run($this, ['as' => World::OWNER, ...clientScenarios()[$fixture]]))->toMatchApiFixture($fixture);
})->with(array_keys(clientScenarios()));
