<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Snapshots\ApplicationResponseSnapshotTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Extensions\Attributes\ApplicationApi;
use Pterodactyl\Extensions\Fields;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Services\Extensions\ExtensionFieldRegistry;
use Pterodactyl\Services\Extensions\ExtensionRegistration;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;
use Pterodactyl\Tests\Integration\Api\Snapshots\SnapshotWorld as World;

/*
 * Golden snapshots of every Application API response that renders a resource, so a change to
 * how responses are built cannot change a byte unnoticed. Each dataset entry is one scenario: a fresh deterministic world (see SnapshotWorld), one actor, and the list
 * of requests whose raw responses make up its snapshot file.
 */

uses(ApplicationApiIntegrationTestCase::class);

#[ApplicationApi]
final class SnapshotPlanFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['plan' => ['required', 'in:free,pro']];
    }
}

final class SnapshotNotesFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['notes' => ['nullable', 'string']];
    }
}

beforeEach(function (): void {
    World::build();
    $this->withHeader('Authorization', World::applicationKey());
});

afterEach(function (): void {
    World::tearDown();

    if (isset($this->extensionsDirectory)) {
        File::deleteDirectory($this->extensionsDirectory);
    }
});

const APP = '/api/application';

/** The includes of each Application API resource and the deeper include strings exercised. */
const APPLICATION_INCLUDES = [
    'egg' => [['servers', 'config', 'script', 'variables', 'tags'], 'servers.user', 'servers.user.servers', 'servers:limit(1|0).user'],
    'location' => [['nodes', 'servers'], 'nodes.allocations', 'nodes.allocations.server', 'nodes:limit(1|0).location'],
    'node' => [['allocations', 'location', 'servers'], 'servers.egg', 'servers.egg.variables', 'location:sort(id|desc).nodes'],
    'allocation' => [['node', 'server'], 'server.user', 'server.user.servers', 'server:limit(1|0).node'],
    'server' => [['allocations', 'user', 'subusers', 'egg', 'variables', 'location', 'node', 'databases', 'transfer'], 'variables.parent,subusers.user,databases.host', 'node.location.nodes', 'user:limit(1|0).servers'],
    'database' => [['password', 'host'], 'host.databases', 'host.databases.host', 'host:limit(1|0).databases'],
    'tag' => [['eggs', 'nodes'], 'eggs.variables', 'eggs.servers.user', 'eggs:limit(1|0).tags'],
    'user' => [['servers'], 'servers.node', 'servers.node.location', 'servers:limit(1|0).egg'],
];

/**
 * @return list<array{0: string, 1: string}>
 */
function applicationMatrix(string $resource, string $uri): array
{
    [$includes, $nested, $deep, $modifier] = APPLICATION_INCLUDES[$resource];

    return World::includes($uri, $includes, $nested, $deep, $modifier);
}

/**
 * Every Application GET route with its include matrix and, for lists, its pagination states.
 *
 * @return array<string, array{0: array<string, mixed>}>
 */
function applicationReads(): array
{
    return [
        'eggs index' => [['requests' => [...applicationMatrix('egg', APP.'/eggs'), ...World::pages(APP.'/eggs', APP.'/eggs?filter[name]=no-such-egg')]]],
        'eggs view' => [['requests' => [...applicationMatrix('egg', APP.'/eggs/1'), ['GET', APP.'/eggs/2?include=config,script'], ['GET', APP.'/eggs/3?include=servers,config,script,variables,tags']]]],
        'locations index' => [['requests' => [...applicationMatrix('location', APP.'/locations'), ...World::pages(APP.'/locations', APP.'/locations?filter[short]=none')]]],
        'locations view' => [['requests' => [...applicationMatrix('location', APP.'/locations/1'), ['GET', APP.'/locations/3?include=nodes,servers']]]],
        'nodes index' => [['requests' => [...applicationMatrix('node', APP.'/nodes'), ...World::pages(APP.'/nodes', APP.'/nodes?filter[name]=none')]]],
        'nodes deployable' => [['requests' => [
            ...applicationMatrix('node', APP.'/nodes/deployable?memory=128&disk=128'),
            ['GET', APP.'/nodes/deployable?memory=128&disk=128&location_ids[]=1&page=1&per_page=1&include=location'],
        ]]],
        'nodes view' => [['requests' => [...applicationMatrix('node', APP.'/nodes/1'), ['GET', APP.'/nodes/2?include=allocations,location,servers']]]],
        'nodes allocations' => [['requests' => [
            ...applicationMatrix('allocation', APP.'/nodes/1/allocations'),
            ...World::pages(APP.'/nodes/1/allocations', APP.'/nodes/1/allocations?filter[ip]=203.0.113.250'),
            ['GET', APP.'/nodes/1/allocations?filter[server_id]=&include=server'],
        ]]],
        'servers index' => [['requests' => [
            ...applicationMatrix('server', APP.'/servers'),
            ...World::pages(APP.'/servers', APP.'/servers?filter[name]=none'),
            ['GET', APP.'/servers?search=Surv&include=user'],
        ]]],
        'servers view' => [['requests' => [...applicationMatrix('server', APP.'/servers/1'), ['GET', APP.'/servers/2?include=transfer,egg,variables']]]],
        'servers external' => [['requests' => applicationMatrix('server', APP.'/servers/external/ext-survival')]],
        'servers databases index' => [['requests' => [...applicationMatrix('database', APP.'/servers/1/databases'), ['GET', APP.'/servers/2/databases']]]],
        'servers databases view' => [['requests' => applicationMatrix('database', APP.'/servers/1/databases/1')]],
        'tags index' => [['requests' => [...applicationMatrix('tag', APP.'/tags'), ...World::pages(APP.'/tags', APP.'/tags?filter[slug]=none')]]],
        'tags view' => [['requests' => [...applicationMatrix('tag', APP.'/tags/1'), ['GET', APP.'/tags/4?include=eggs,nodes']]]],
        'users index' => [['requests' => [...applicationMatrix('user', APP.'/users'), ...World::pages(APP.'/users', APP.'/users?filter[email]=none@example.test')]]],
        'users view' => [['requests' => [...applicationMatrix('user', APP.'/users/2'), ['GET', APP.'/users/5?include=servers']]]],
        'users external' => [['requests' => applicationMatrix('user', APP.'/users/external/ext-owner')]],
    ];
}

/**
 * One scenario per ACL resource: a key without access to that resource requests every
 * endpoint it can still reach with all includes, so every gated include is a null_resource.
 *
 * @return array<string, array{0: array<string, mixed>}>
 */
function applicationAclScenarios(): array
{
    $endpoints = [
        AdminAcl::RESOURCE_EGGS => [APP.'/eggs/1?include=servers,config,script,variables,tags,servers.user', APP.'/tags/1?include=eggs,nodes,eggs.variables'],
        AdminAcl::RESOURCE_LOCATIONS => [APP.'/locations/1?include=nodes,servers,nodes.location'],
        AdminAcl::RESOURCE_NODES => [APP.'/nodes/1?include=allocations,location,servers,servers.node'],
        AdminAcl::RESOURCE_ALLOCATIONS => [APP.'/nodes/1/allocations?include=node,server,server.allocations'],
        AdminAcl::RESOURCE_SERVERS => [APP.'/servers/1?include=allocations,user,subusers,egg,variables,location,node,databases,transfer,variables.parent,subusers.user,subusers.server,databases.host,allocations.node'],
        AdminAcl::RESOURCE_SERVER_DATABASES => [APP.'/servers/1/databases/1?include=password,host,host.databases'],
        AdminAcl::RESOURCE_USERS => [APP.'/users/2?include=servers,servers.user,servers.subusers'],
    ];

    $scenarios = [];
    foreach (AdminAcl::getResourceList() as $resource) {
        $requests = [];
        foreach ($endpoints as $own => $uris) {
            if ($own === $resource) {
                continue;
            }

            foreach ($uris as $uri) {
                $requests[] = ['GET', $uri];
            }
        }

        $scenarios['acl without '.$resource] = [['key' => [AdminAcl::COLUMN_IDENTIFIER.$resource => AdminAcl::NONE], 'requests' => $requests]];
    }

    return $scenarios;
}

/**
 * Every Application write endpoint that returns a body.
 *
 * @return array<string, array{0: array<string, mixed>}>
 */
function applicationWrites(): array
{
    $node = ['name' => 'node-gamma', 'description' => 'Created through the API', 'location_id' => 1, 'public' => true, 'fqdn' => 'gamma.example.test', 'scheme' => 'https', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'upload_size' => 100, 'daemon_listen' => 8080, 'daemon_sftp' => 2022, 'daemon_base' => '/var/lib/pterodactyl/volumes'];
    $environment = ['SERVER_JARFILE' => 'server.jar', 'BUILD_NUMBER' => '42', 'HIDDEN_TOKEN' => 'abc'];
    $user = ['username' => 'owner', 'email' => 'owner@example.test', 'first_name' => 'Olivia', 'last_name' => 'Owner', 'language' => 'en'];

    return [
        'write locations store' => [['requests' => [['POST', APP.'/locations', ['short' => 'ap-south', 'long' => 'Asia Pacific "South"']]]]],
        'write locations update' => [['requests' => [['PATCH', APP.'/locations/1', ['short' => 'us-east-1', 'long' => 'US East (renamed)']]]]],
        'write nodes store' => [['requests' => [['POST', APP.'/nodes', $node]]]],
        'write nodes update' => [['requests' => [['PATCH', APP.'/nodes/1', [...$node, 'name' => 'node-alpha-renamed', 'fqdn' => 'alpha.example.test', 'memory' => 16384]]]]],
        'write servers store' => [['requests' => [['POST', APP.'/servers', [
            'name' => 'Created Server', 'description' => 'Created through the API', 'external_id' => 'ext-created', 'user' => World::OWNER, 'egg' => 1,
            'docker_image' => 'ghcr.io/pterodactyl/yolks:java_21', 'startup' => 'java -jar {{SERVER_JARFILE}}', 'environment' => $environment,
            'limits' => ['memory' => 512, 'swap' => 0, 'disk' => 1024, 'io' => 500, 'cpu' => 100], 'feature_limits' => ['databases' => 1, 'allocations' => 2, 'backups' => 3],
            'allocation' => ['default' => 4, 'additional' => [5]], 'start_on_completion' => false,
        ]]]]],
        'write servers build' => [['requests' => [['PATCH', APP.'/servers/1/build', [
            'allocation' => 1, 'add_allocations' => [4], 'remove_allocations' => [2],
            'limits' => ['memory' => 4096, 'swap' => 0, 'io' => 500, 'cpu' => 300, 'disk' => 20480, 'threads' => '0-1'],
            'feature_limits' => ['databases' => 6, 'backups' => 7, 'allocations' => 8],
        ]]]]],
        'write servers details' => [['requests' => [['PATCH', APP.'/servers/1/details', ['name' => 'Survival Renamed', 'user' => World::HELPER, 'external_id' => 'ext-survival-2', 'description' => 'Renamed via API']]]]],
        'write servers startup' => [['requests' => [['PATCH', APP.'/servers/1/startup', ['startup' => 'java -jar {{SERVER_JARFILE}}', 'environment' => [...$environment, 'SERVER_JARFILE' => 'renamed.jar'], 'egg' => 1, 'image' => 'ghcr.io/pterodactyl/yolks:java_17', 'skip_scripts' => true]]]]],
        'write servers databases store' => [['redact' => ['password'], 'requests' => [['POST', APP.'/servers/1/databases', ['database' => 'apidb', 'remote' => '%', 'host' => 1]]]]],
        'write servers databases store with includes' => [['redact' => ['password'], 'requests' => [['POST', APP.'/servers/1/databases?include=password,host', ['database' => 'apidb', 'remote' => '10.%', 'host' => 1]]]]],
        'write users store' => [['requests' => [['POST', APP.'/users', ['username' => 'created', 'email' => 'created@example.test', 'first_name' => 'Cre', 'last_name' => 'Ated', 'external_id' => 'ext-created']]]]],
        'write users update' => [['requests' => [['PATCH', APP.'/users/2', $user]]]],
        'write users update with body include' => [['requests' => [['PATCH', APP.'/users/2', [...$user, 'include' => 'servers']]]]],
    ];
}

/**
 * Endpoints that call withExtensionFields(), with one extension marked for the Application
 * API and one that is not, so only the marked one is returned.
 *
 * @return array<string, array{0: array<string, mixed>}>
 */
function applicationExtensionFields(): array
{
    $setup = function (): void {
        $this->extensionsDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-snapshot-application-extensions';
        File::deleteDirectory($this->extensionsDirectory);
        config(['extensions.enabled' => true, 'extensions.directory' => $this->extensionsDirectory]);

        foreach (['billing' => SnapshotPlanFields::class, 'notes' => SnapshotNotesFields::class] as $identifier => $fields) {
            File::ensureDirectoryExists($this->extensionsDirectory.DIRECTORY_SEPARATOR.$identifier);
            File::put($this->extensionsDirectory.DIRECTORY_SEPARATOR.$identifier.DIRECTORY_SEPARATOR.'extension.json', json_encode(['id' => $identifier, 'name' => $identifier, 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
            Extension::query()->create(['identifier' => $identifier, 'version' => '1.0.0', 'enabled' => true]);
            foreach ([User::class, Server::class, Node::class, Location::class] as $model) {
                $this->app->make(ExtensionFieldRegistry::class)->register($identifier, $model, $fields, $this->app->make(ExtensionRegistration::class));
            }
        }

        $repository = $this->app->make(ExtensionRepository::class);
        $repository->flushDiscovery();
        foreach ([User::query()->findOrFail(World::OWNER), Server::query()->findOrFail(1), Node::query()->findOrFail(1), Location::query()->findOrFail(1)] as $model) {
            $repository->settings('billing')->fields($model)->set('plan', 'pro');
            $repository->settings('notes')->fields($model)->set('notes', 'not for the application api');
        }
    };

    return ['extension fields' => [['setup' => $setup, 'requests' => [
        ['GET', APP.'/users/2'],
        ['GET', APP.'/users/3'],
        ['GET', APP.'/users/2?include=servers'],
        ['GET', APP.'/servers/1?include=user,node,location'],
        ['GET', APP.'/servers/1'],
        ['GET', APP.'/nodes/1'],
        ['GET', APP.'/locations/1'],
        ['GET', APP.'/users?per_page=1'],
        ['PATCH', APP.'/users/2', ['username' => 'owner', 'email' => 'owner@example.test', 'first_name' => 'Olive', 'last_name' => 'Owner', 'extensions' => ['billing' => ['plan' => 'free']]]],
    ]]]];
}

test('application api responses match their snapshots', function (array $scenario): void {
    expect(World::run($this, $scenario))->toMatchSnapshot();
})->with([...applicationReads(), ...applicationAclScenarios(), ...applicationWrites(), ...applicationExtensionFields()]);
