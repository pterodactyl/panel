<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Snapshots\ClientResponseSnapshotTest;

use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Integration\Api\Snapshots\SnapshotWorld as World;

/*
 * Golden snapshots of every Fractal-backed Client API response, recorded before the Fractal
 * to API Resources port so the port can prove it changed no byte. Each dataset entry is one
 * scenario: a fresh deterministic world (see SnapshotWorld), one user (the server owner unless
 * the scenario names another), and the list of requests whose raw responses make up its
 * snapshot file. Subusers without the permissions that gate an include get their own
 * scenarios, since those includes become null resources or are narrowed for them.
 */

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

/** The includes of each Client API resource and the deeper include strings exercised. */
const CLIENT_INCLUDES = [
    'server' => [['egg', 'subusers'], 'egg.nope', 'subusers.a.b', 'egg:limit(1|0).nope'],
    'activity' => [['actor'], 'actor.nope', 'actor.a.b', 'actor:limit(1|0)'],
    'database' => [['password'], 'password.nope', 'password.a.b', 'password:limit(1|0)'],
    'schedule' => [['tasks'], 'tasks.nope', 'tasks.a.b', 'tasks:limit(1|0)'],
];

/**
 * @return list<array{0: string, 1: string}>
 */
function clientMatrix(?string $resource, string $uri): array
{
    if ($resource === null) {
        return World::includes($uri);
    }

    [$includes, $nested, $deep, $modifier] = CLIENT_INCLUDES[$resource];

    return World::includes($uri, $includes, $nested, $deep, $modifier);
}

/**
 * @param  list<string>  $uris
 * @return list<array{0: string, 1: string}>
 */
function gets(array $uris): array
{
    return array_map(fn (string $uri): array => ['GET', $uri], $uris);
}

/**
 * Every Client GET route with its include matrix and, for lists, its pagination states,
 * including lists whose links carry the query through appends().
 *
 * @return array<string, array{0: array<string, mixed>}>
 */
function clientReads(): array
{
    return [
        'servers index' => [['requests' => [
            ...clientMatrix('server', CLIENT),
            ['GET', CLIENT.'?include=allocations,variables,egg,subusers'],
            ...World::pages(CLIENT, CLIENT.'?filter[name]=none'),
            ['GET', CLIENT.'?per_page=1&page=1&filter[uuid]=5e7f&include=egg'],
            ['GET', CLIENT.'?type=owner&per_page=1&page=2&include=subusers'],
        ]]],
        'servers index as admin' => [['as' => World::ADMIN, 'requests' => gets([
            CLIENT, CLIENT.'?type=admin', CLIENT.'?type=admin-all&per_page=2&include=egg', CLIENT.'?type=admin-all&per_page=2&page=2', CLIENT.'?type=owner',
        ])]],
        'account' => [['requests' => clientMatrix(null, CLIENT.'/account')]],
        'account activity' => [['requests' => [
            ...clientMatrix('activity', CLIENT.'/account/activity'),
            ...World::pages(CLIENT.'/account/activity', CLIENT.'/account/activity?filter[event]=none'),
            ['GET', CLIENT.'/account/activity?per_page=1&page=2&sort=-timestamp&include=actor'],
        ]]],
        'account api keys' => [['requests' => clientMatrix(null, CLIENT.'/account/api-keys')]],
        'account ssh keys' => [['requests' => clientMatrix(null, CLIENT.'/account/ssh-keys')]],
        'account empty' => [['as' => World::SPARE, 'requests' => gets([
            CLIENT, CLIENT.'/account', CLIENT.'/account/activity', CLIENT.'/account/api-keys', CLIENT.'/account/ssh-keys',
        ])]],
        'server view' => [['requests' => [...clientMatrix('server', SERVER), ['GET', SERVER.'?include=allocations,variables,egg,subusers']]]],
        'server activity' => [['requests' => [
            ...clientMatrix('activity', SERVER.'/activity'),
            ...World::pages(SERVER.'/activity', SERVER.'/activity?filter[event]=none'),
            ['GET', SERVER.'/activity?per_page=2&page=2&sort=timestamp&filter[event]=server:&include=actor'],
        ]]],
        'server backups' => [['requests' => [...clientMatrix(null, SERVER.'/backups'), ...World::pages(SERVER.'/backups')]]],
        'server backups view' => [['requests' => [
            ...clientMatrix(null, SERVER.'/backups/'.World::BACKUP_NIGHTLY),
            ['GET', SERVER.'/backups/'.World::BACKUP_FAILED],
            ['GET', SERVER.'/backups/'.World::BACKUP_RUNNING],
        ]]],
        'server databases' => [['requests' => clientMatrix('database', SERVER.'/databases')]],
        'server files' => [['requests' => [...clientMatrix(null, SERVER.'/files/list'), ['GET', SERVER.'/files/list?directory=/plugins']]]],
        'server network allocations' => [['requests' => clientMatrix(null, SERVER.'/network/allocations')]],
        'server resources' => [['requests' => clientMatrix(null, SERVER.'/resources')]],
        'server schedules' => [['requests' => clientMatrix('schedule', SERVER.'/schedules')]],
        'server schedules view' => [['requests' => [...clientMatrix('schedule', SERVER.'/schedules/1'), ['GET', SERVER.'/schedules/2']]]],
        'server startup' => [['requests' => clientMatrix(null, SERVER.'/startup')]],
        'server subusers' => [['requests' => [
            ...clientMatrix(null, SERVER.'/users'),
            ['GET', SERVER.'/users/'.World::HELPER_UUID],
            ['GET', SERVER.'/users/'.World::LIMITED_UUID.'?include=does-not-exist'],
        ]]],
        'server as full subuser' => [['as' => World::HELPER, 'requests' => gets([
            CLIENT.'?include=egg,subusers', SERVER.'?include=egg,subusers', SERVER.'/databases?include=password', SERVER.'/activity?include=actor',
            SERVER.'/network/allocations', SERVER.'/startup', SERVER.'/users', SERVER.'/resources',
        ])]],
        'server as limited subuser' => [['as' => World::LIMITED, 'requests' => gets([
            CLIENT, CLIENT.'?include=egg,subusers', SERVER, SERVER.'?include=egg,subusers', SERVER.'/databases?include=password',
            SERVER.'/activity?include=actor', SERVER.'/backups', SERVER.'/schedules',
        ])]],
        // The administrator's own server has none of the resources the owner's server has,
        // so its lists are the empty ones; its node leaves maintenance so they can be read.
        'server as admin' => [['as' => World::ADMIN, 'setup' => fn () => Node::query()->whereKey(2)->update(['maintenance_mode' => false]), 'requests' => gets([
            SERVER.'?include=egg,subusers', SERVER.'/activity?include=actor', SERVER.'/databases?include=password',
            LAB, LAB.'/backups', LAB.'/schedules', LAB.'/users', LAB.'/network/allocations', LAB.'/startup', LAB.'/databases', LAB.'/activity',
        ])]],
    ];
}

/**
 * Every Client write endpoint that returns a body.
 *
 * @return array<string, array{0: array<string, mixed>}>
 */
function clientWrites(): array
{
    $cron = ['minute' => '0', 'hour' => '*', 'day_of_week' => '*', 'month' => '*', 'day_of_month' => '*'];

    return [
        'write account api keys store' => [['requests' => [['POST', CLIENT.'/account/api-keys', ['description' => 'Created key', 'allowed_ips' => ['127.0.0.1', '192.0.2.0/24']]]]]],
        'write account ssh keys store' => [['requests' => [['POST', CLIENT.'/account/ssh-keys', ['name' => 'Workstation', 'public_key' => World::NEW_SSH_KEY]]]]],
        'write server backups store' => [['requests' => [['POST', SERVER.'/backups', ['name' => 'Manual backup', 'is_locked' => true, 'ignored' => "logs/\ncache/"]]]]],
        'write server backups lock' => [['requests' => [['POST', SERVER.'/backups/'.World::BACKUP_FAILED.'/lock']]]],
        'write server databases store' => [['redact' => ['password'], 'requests' => [['POST', SERVER.'/databases', ['database' => 'clientdb', 'remote' => '%']]]]],
        'write server databases rotate password' => [['redact' => ['password'], 'requests' => [['POST', SERVER.'/databases/1/rotate-password']]]],
        'write server databases as limited subuser' => [['as' => World::LIMITED, 'redact' => ['password'], 'requests' => [
            ['POST', SERVER.'/databases', ['database' => 'limiteddb', 'remote' => '%']],
            ['POST', SERVER.'/databases/2/rotate-password'],
        ]]],
        'write server files compress' => [['requests' => [['POST', SERVER.'/files/compress', ['root' => '/', 'files' => ['plugins', 'server.properties']]]]]],
        'write server network allocations store' => [['requests' => [['POST', SERVER.'/network/allocations']]]],
        'write server network allocations update' => [['requests' => [['POST', SERVER.'/network/allocations/2', ['notes' => 'Updated <notes> & "quotes"']]]]],
        'write server network allocations primary' => [['requests' => [['POST', SERVER.'/network/allocations/2/primary']]]],
        'write server schedules store' => [['redact' => ['next_run_at'], 'requests' => [['POST', SERVER.'/schedules', ['name' => 'Hourly backup', 'is_active' => true, 'only_when_online' => false, ...$cron]]]]],
        'write server schedules update' => [['redact' => ['next_run_at'], 'requests' => [['POST', SERVER.'/schedules/1', ['name' => 'Nightly restart (edited)', 'is_active' => false, ...$cron, 'minute' => '5', 'hour' => '3']]]]],
        'write server schedule tasks store' => [['requests' => [['POST', SERVER.'/schedules/1/tasks', ['action' => 'command', 'payload' => 'say hello', 'time_offset' => 10, 'sequence_id' => 1, 'continue_on_failure' => true]]]]],
        'write server schedule tasks update' => [['requests' => [['POST', SERVER.'/schedules/1/tasks/1', ['action' => 'command', 'payload' => 'say "edited"', 'time_offset' => 30]]]]],
        'write server startup variable' => [['requests' => [['PUT', SERVER.'/startup/variable', ['key' => 'SERVER_JARFILE', 'value' => 'custom.jar']]]]],
        'write server subusers store' => [['requests' => [['POST', SERVER.'/users', ['email' => 'new.subuser@example.test', 'permissions' => ['control.console', 'file.read']]]]]],
        'write server subusers update' => [['requests' => [['POST', SERVER.'/users/'.World::HELPER_UUID, ['permissions' => ['control.console', 'control.start', 'control.stop']]]]]],
    ];
}

test('client api responses match their snapshots', function (array $scenario): void {
    expect(World::run($this, [...$scenario, 'as' => $scenario['as'] ?? World::OWNER]))->toMatchSnapshot();
})->with([...clientReads(), ...clientWrites()]);
