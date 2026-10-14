<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Fixtures\ApplicationApiFixturesTest;

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
use Pterodactyl\Tests\Integration\Api\Fixtures\ApiFixtureWorld as World;

uses(ApplicationApiIntegrationTestCase::class);

#[ApplicationApi]
final class PlanFields extends Fields
{
    public function rules(): array
    {
        return ['plan' => ['required', 'in:free,pro']];
    }
}

final class NotesFields extends Fields
{
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

const APPLICATION_INCLUDES = [
    'egg' => ['servers', 'config', 'script', 'variables', 'tags'],
    'location' => ['nodes', 'servers'],
    'node' => ['allocations', 'location', 'servers'],
    'allocation' => ['node', 'server'],
    'server' => ['allocations', 'user', 'subusers', 'egg', 'variables', 'location', 'node', 'databases', 'transfer'],
    'database' => ['password', 'host'],
    'tag' => ['eggs', 'nodes'],
    'user' => ['servers'],
];

function applicationReads(): array
{
    $deployable = APP.'/nodes/deployable?memory=128&disk=128';

    return [
        'application/eggs.index' => ['requests' => [
            ...World::allIncludes(APP.'/eggs', APPLICATION_INCLUDES['egg']),
            ...World::pages(APP.'/eggs', APP.'/eggs?filter[name]=no-such-egg'),
        ]],
        'application/eggs.view' => ['requests' => [
            ...World::eachInclude(APP.'/eggs/1', APPLICATION_INCLUDES['egg'], 'servers.user'),
            ['GET', APP.'/eggs/2?include=config,script'],
            ['GET', APP.'/eggs/3?include=servers,config,script,variables,tags'],
        ]],
        'application/locations.index' => ['requests' => [
            ...World::allIncludes(APP.'/locations', APPLICATION_INCLUDES['location']),
            ...World::pages(APP.'/locations', APP.'/locations?filter[short]=none'),
        ]],
        'application/locations.view' => ['requests' => [
            ...World::eachInclude(APP.'/locations/1', APPLICATION_INCLUDES['location'], 'nodes.allocations'),
            ['GET', APP.'/locations/3?include=nodes,servers'],
        ]],
        'application/nodes.index' => ['requests' => [
            ...World::allIncludes(APP.'/nodes', APPLICATION_INCLUDES['node']),
            ['GET', APP.'/nodes?include=tags,deployment_tags'],
            ...World::pages(APP.'/nodes', APP.'/nodes?filter[name]=none'),
        ]],
        'application/nodes.view' => ['requests' => [
            ...World::eachInclude(APP.'/nodes/1', APPLICATION_INCLUDES['node'], 'servers.egg'),
            ['GET', APP.'/nodes/1?include=tags'],
            ['GET', APP.'/nodes/2?include=allocations,location,servers'],
            ['GET', APP.'/nodes/2?include=deployment_tags.nodes'],
        ]],
        'application/nodes/deployable.index' => ['requests' => [
            ...World::allIncludes($deployable, APPLICATION_INCLUDES['node']),
            ['GET', $deployable.'&location_ids[]=1&page=1&per_page=1&include=location'],
            ['GET', $deployable.'&egg=1'],
            ['GET', $deployable.'&egg=3'],
            ['GET', $deployable.'&tags[]=reserved'],
            ['GET', $deployable.'&tags[]=3'],
            ['GET', $deployable.'&tags[]=999&egg=2'],
            ['GET', $deployable.'&tags[]=missing&egg=999'],
        ]],
        'application/nodes/allocations.index' => ['requests' => [
            ...World::eachInclude(APP.'/nodes/1/allocations', APPLICATION_INCLUDES['allocation'], 'server.user'),
            ...World::pages(APP.'/nodes/1/allocations', APP.'/nodes/1/allocations?filter[ip]=203.0.113.250'),
            ['GET', APP.'/nodes/1/allocations?filter[server_id]=&include=server'],
        ]],
        'application/servers.index' => ['requests' => [
            ...World::allIncludes(APP.'/servers', APPLICATION_INCLUDES['server']),
            ...World::pages(APP.'/servers', APP.'/servers?filter[name]=none'),
            ['GET', APP.'/servers?search=Surv&include=user'],
        ]],
        'application/servers.view' => ['requests' => [
            ...World::eachInclude(APP.'/servers/1', APPLICATION_INCLUDES['server'], 'variables.parent,subusers.user,databases.host'),
            ['GET', APP.'/servers/2?include=transfer,egg,variables'],
        ]],
        'application/servers/external.view' => ['requests' => World::allIncludes(APP.'/servers/external/ext-survival', APPLICATION_INCLUDES['server'])],
        'application/servers/databases.index' => ['requests' => [
            ...World::allIncludes(APP.'/servers/1/databases', APPLICATION_INCLUDES['database']),
            ['GET', APP.'/servers/2/databases'],
        ]],
        'application/servers/databases.view' => ['requests' => World::eachInclude(APP.'/servers/1/databases/1', APPLICATION_INCLUDES['database'], 'host.databases')],
        'application/tags.index' => ['requests' => [
            ...World::allIncludes(APP.'/tags', APPLICATION_INCLUDES['tag']),
            ...World::pages(APP.'/tags', APP.'/tags?filter[slug]=none'),
            ['GET', APP.'/tags?filter[legacy_nest_id]=1'],
            ['GET', APP.'/tags?filter[legacy_nest_id]=7&include=eggs'],
            ['GET', APP.'/tags?filter[legacy_nest_id]=99'],
        ]],
        'application/tags.view' => ['requests' => [
            ...World::eachInclude(APP.'/tags/1', APPLICATION_INCLUDES['tag'], 'eggs.variables'),
            ['GET', APP.'/tags/4?include=eggs,nodes'],
        ]],
        'application/users.index' => ['requests' => [
            ...World::allIncludes(APP.'/users', APPLICATION_INCLUDES['user']),
            ...World::pages(APP.'/users', APP.'/users?filter[email]=none@example.test'),
        ]],
        'application/users.view' => ['requests' => [
            ...World::eachInclude(APP.'/users/2', APPLICATION_INCLUDES['user'], 'servers.node'),
            ['GET', APP.'/users/2?include=does-not-exist'],
            ['GET', APP.'/users/5?include=servers'],
        ]],
        'application/users/external.view' => ['requests' => World::allIncludes(APP.'/users/external/ext-owner', APPLICATION_INCLUDES['user'])],
    ];
}

/** A key without access to a resource renders it as a null resource wherever another endpoint would include it. */
function applicationAcl(): array
{
    $eggs = APP.'/eggs/1?include=servers,config,script,variables,tags,servers.user';
    $tags = APP.'/tags/1?include=eggs,nodes,eggs.variables';
    $locations = APP.'/locations/1?include=nodes,servers,nodes.location';
    $nodes = APP.'/nodes/1?include=allocations,location,servers,servers.node';
    $allocations = APP.'/nodes/1/allocations?include=node,server,server.allocations';
    $servers = APP.'/servers/1?include=allocations,user,subusers,egg,variables,location,node,databases,transfer,variables.parent,subusers.user,subusers.server,databases.host,allocations.node';
    $database = APP.'/servers/1/databases/1?include=password,host,host.databases';
    $users = APP.'/users/2?include=servers,servers.user,servers.subusers';

    $without = fn (string $resource, string ...$uris): array => [
        'key' => [AdminAcl::COLUMN_IDENTIFIER.$resource => AdminAcl::NONE],
        'requests' => array_map(fn (string $uri): array => ['GET', $uri], $uris),
    ];

    return [
        'application/acl/without-allocations' => $without(AdminAcl::RESOURCE_ALLOCATIONS, $nodes, $servers),
        'application/acl/without-database-hosts' => $without(AdminAcl::RESOURCE_DATABASE_HOSTS, $servers, $database),
        'application/acl/without-eggs' => $without(AdminAcl::RESOURCE_EGGS, APP.'/nodes/1?include=tags,deployment_tags', $servers),
        'application/acl/without-locations' => $without(AdminAcl::RESOURCE_LOCATIONS, $nodes, $servers),
        'application/acl/without-nodes' => $without(AdminAcl::RESOURCE_NODES, $tags, $locations, $allocations, $servers),
        'application/acl/without-server-databases' => $without(AdminAcl::RESOURCE_SERVER_DATABASES, $servers),
        'application/acl/without-servers' => $without(AdminAcl::RESOURCE_SERVERS, $eggs, $locations, $nodes, $allocations, $users),
        'application/acl/without-users' => $without(AdminAcl::RESOURCE_USERS, $eggs, $servers),
    ];
}

function applicationWrites(): array
{
    // A node's fqdn has to resolve, and only localhost does without DNS.
    $node = ['name' => 'node-gamma', 'description' => 'Created through the API', 'location_id' => 1, 'public' => true, 'fqdn' => 'localhost', 'scheme' => 'https', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'upload_size' => 100, 'daemon_listen' => 8080, 'daemon_sftp' => 2022, 'daemon_base' => '/var/lib/pterodactyl/volumes'];
    $environment = ['SERVER_JARFILE' => 'server.jar', 'BUILD_NUMBER' => '42', 'HIDDEN_TOKEN' => 'abc'];
    $user = ['username' => 'owner', 'email' => 'owner@example.test', 'first_name' => 'Olivia', 'last_name' => 'Owner', 'language' => 'en'];

    return [
        'application/locations.store' => ['requests' => [['POST', APP.'/locations', ['short' => 'ap-south', 'long' => 'Asia Pacific "South"']]]],
        'application/locations.update' => ['requests' => [['PATCH', APP.'/locations/1', ['short' => 'us-east-1', 'long' => 'US East (renamed)']]]],
        'application/nodes.store' => ['requests' => [['POST', APP.'/nodes', $node]]],
        'application/nodes.update' => ['requests' => [['PATCH', APP.'/nodes/1', [...$node, 'name' => 'node-alpha-renamed', 'memory' => 16384]]]],
        'application/servers.store' => ['requests' => [['POST', APP.'/servers', [
            'name' => 'Created Server', 'description' => 'Created through the API', 'external_id' => 'ext-created', 'user' => World::OWNER, 'egg' => 1,
            'docker_image' => 'ghcr.io/pterodactyl/yolks:java_21', 'startup' => 'java -jar {{SERVER_JARFILE}}', 'environment' => $environment,
            'limits' => ['memory' => 512, 'swap' => 0, 'disk' => 1024, 'io' => 500, 'cpu' => 100], 'feature_limits' => ['databases' => 1, 'allocations' => 2, 'backups' => 3],
            'allocation' => ['default' => 4, 'additional' => [5]], 'start_on_completion' => false,
        ]]]],
        'application/servers/build.update' => ['requests' => [['PATCH', APP.'/servers/1/build', [
            'allocation' => 1, 'add_allocations' => [4], 'remove_allocations' => [2],
            'limits' => ['memory' => 4096, 'swap' => 0, 'io' => 500, 'cpu' => 300, 'disk' => 20480, 'threads' => '0-1'],
            'feature_limits' => ['databases' => 6, 'backups' => 7, 'allocations' => 8],
        ]]]],
        'application/servers/details.update' => ['requests' => [['PATCH', APP.'/servers/1/details', ['name' => 'Survival Renamed', 'user' => World::HELPER, 'external_id' => 'ext-survival-2', 'description' => 'Renamed via API']]]],
        'application/servers/startup.update' => ['requests' => [['PATCH', APP.'/servers/1/startup', ['startup' => 'java -jar {{SERVER_JARFILE}}', 'environment' => [...$environment, 'SERVER_JARFILE' => 'renamed.jar'], 'egg' => 1, 'image' => 'ghcr.io/pterodactyl/yolks:java_17', 'skip_scripts' => true]]]],
        'application/servers/databases.store' => ['redact' => ['password'], 'requests' => [['POST', APP.'/servers/1/databases', ['database' => 'apidb', 'remote' => '%', 'host' => 1]]]],
        'application/servers/databases.store.includes' => ['redact' => ['password'], 'requests' => [['POST', APP.'/servers/1/databases?include=password,host', ['database' => 'apidb', 'remote' => '10.%', 'host' => 1]]]],
        'application/users.store' => ['requests' => [['POST', APP.'/users', ['username' => 'created', 'email' => 'created@example.test', 'first_name' => 'Cre', 'last_name' => 'Ated', 'external_id' => 'ext-created']]]],
        'application/users.update' => ['requests' => [['PATCH', APP.'/users/2', $user]]],
        'application/users.update.body-include' => ['requests' => [['PATCH', APP.'/users/2', [...$user, 'include' => 'servers']]]],
    ];
}

/** Only the extension marked for the Application API has its fields returned. */
function applicationExtensionFields(): array
{
    $setup = function (): void {
        $this->extensionsDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-fixture-application-extensions';
        File::deleteDirectory($this->extensionsDirectory);
        config(['extensions.enabled' => true, 'extensions.directory' => $this->extensionsDirectory]);

        foreach (['billing' => PlanFields::class, 'notes' => NotesFields::class] as $identifier => $fields) {
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

    return ['application/extension-fields' => ['setup' => $setup, 'requests' => [
        ['GET', APP.'/users/2'],
        ['GET', APP.'/users/3'],
        ['GET', APP.'/users/2?include=servers'],
        ['GET', APP.'/servers/1?include=user,node,location'],
        ['GET', APP.'/servers/1'],
        ['GET', APP.'/nodes/1'],
        ['GET', APP.'/locations/1'],
        ['GET', APP.'/users?per_page=1'],
        ['PATCH', APP.'/users/2', ['username' => 'owner', 'email' => 'owner@example.test', 'first_name' => 'Olive', 'last_name' => 'Owner', 'extensions' => ['billing' => ['plan' => 'free']]]],
    ]]];
}

function applicationScenarios(): array
{
    return [...applicationReads(), ...applicationAcl(), ...applicationWrites(), ...applicationExtensionFields()];
}

test('application api responses match their fixtures', function (string $fixture): void {
    expect(World::run($this, applicationScenarios()[$fixture]))->toMatchApiFixture($fixture);
})->with(array_keys(applicationScenarios()));
