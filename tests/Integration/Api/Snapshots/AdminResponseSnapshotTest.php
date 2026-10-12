<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Snapshots\AdminResponseSnapshotTest;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Pterodactyl\Contracts\Eggs\ExportsEggs;
use Pterodactyl\Extensions\Fields;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionFieldRegistry;
use Pterodactyl\Services\Extensions\ExtensionRegistration;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;
use Pterodactyl\Tests\Integration\Api\Snapshots\SnapshotWorld as World;

/*
 * Golden snapshots of every Admin API response that renders a resource, so a change to how
 * responses are built cannot change a byte unnoticed. Each dataset entry is one scenario: a fresh deterministic world (see SnapshotWorld) acting as its root administrator,
 * and the list of requests whose raw responses make up its snapshot file.
 */

uses(AdminApiIntegrationTestCase::class);

final class SnapshotAdminNoteFields extends Fields
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:64']];
    }
}

beforeEach(function (): void {
    World::build();
    $this->actingAs(User::query()->findOrFail(World::ADMIN));
});

afterEach(function (): void {
    World::tearDown();

    if (isset($this->extensionsDirectory)) {
        File::deleteDirectory($this->extensionsDirectory);
    }
});

const ADMIN = '/api/admin';

/** The includes of each Admin API resource and the deeper include strings exercised. */
const ADMIN_INCLUDES = [
    'activity' => [['actor'], 'actor.servers', 'actor.servers.node', 'actor:limit(1|0).servers'],
    'database_host' => [['node', 'databases'], 'node.location', 'node.location.servers', 'node:limit(1|0).location'],
    'egg' => [['variables', 'tags', 'servers'], 'servers.user', 'servers.user.servers', 'servers:limit(1|0).node'],
    'location' => [['nodes', 'servers'], 'nodes.allocations', 'nodes.allocations.server', 'nodes:limit(1|0).location'],
    'mount' => [['eggs', 'nodes', 'servers'], 'eggs.tags', 'nodes.location.nodes', 'eggs:limit(1|0).variables'],
    'node' => [['allocations', 'location', 'servers'], 'allocations.server', 'servers.egg.variables', 'servers:limit(1|0).user'],
    'allocation' => [['node', 'server'], 'server.user', 'node.location.nodes', 'server:limit(1|0).egg'],
    'server' => [['allocation', 'allocations', 'user', 'subusers', 'egg', 'variables', 'location', 'node', 'databases'], 'egg.variables,node.location,databases.password', 'user.servers.node', 'egg:limit(1|0).tags'],
    'database' => [['password'], 'password.nope', 'password.a.b', 'password:limit(1|0)'],
    'tag' => [['eggs', 'nodes'], 'eggs.variables', 'nodes.servers.user', 'eggs:limit(1|0).tags'],
    'user' => [['servers'], 'servers.node', 'servers.node.location', 'servers:limit(1|0).egg'],
];

/**
 * @return list<array{0: string, 1: string}>
 */
function adminMatrix(?string $resource, string $uri): array
{
    if ($resource === null) {
        return World::includes($uri);
    }

    [$includes, $nested, $deep, $modifier] = ADMIN_INCLUDES[$resource];

    return World::includes($uri, $includes, $nested, $deep, $modifier);
}

/**
 * Every Admin GET route with its include matrix and, for lists, its pagination states.
 *
 * @return array<string, array{0: array<string, mixed>}>
 */
function adminReads(): array
{
    return [
        'activity index' => [['requests' => [
            ...adminMatrix('activity', ADMIN.'/activity'),
            ...World::pages(ADMIN.'/activity', ADMIN.'/activity?filter[event]=admin:nothing'),
            ['GET', ADMIN.'/activity?filter[event]=admin:&sort=timestamp&per_page=1&page=2&include=actor'],
        ]]],
        'api keys index' => [['requests' => [...adminMatrix(null, ADMIN.'/api-keys'), ...World::pages(ADMIN.'/api-keys', ADMIN.'/api-keys?filter[memo]=none')]]],
        'api keys view' => [['requests' => [...adminMatrix(null, ADMIN.'/api-keys/'.World::READ_ONLY_KEY), ['GET', ADMIN.'/api-keys/'.World::APPLICATION_KEY]]]],
        'database hosts index' => [['requests' => [...adminMatrix('database_host', ADMIN.'/database-hosts'), ...World::pages(ADMIN.'/database-hosts', ADMIN.'/database-hosts?filter[name]=none')]]],
        'database hosts view' => [['requests' => [...adminMatrix('database_host', ADMIN.'/database-hosts/1'), ['GET', ADMIN.'/database-hosts/2?include=node,databases']]]],
        'database hosts databases' => [['requests' => [...adminMatrix(null, ADMIN.'/database-hosts/1/databases'), ...World::pages(ADMIN.'/database-hosts/1/databases', ADMIN.'/database-hosts/2/databases')]]],
        'eggs index' => [['requests' => [...adminMatrix('egg', ADMIN.'/eggs'), ...World::pages(ADMIN.'/eggs', ADMIN.'/eggs?filter[name]=no-such-egg')]]],
        'eggs view' => [['requests' => [...adminMatrix('egg', ADMIN.'/eggs/1'), ['GET', ADMIN.'/eggs/2'], ['GET', ADMIN.'/eggs/3?include=variables,tags,servers']]]],
        'eggs scripts' => [['requests' => [...adminMatrix('egg', ADMIN.'/eggs/2/scripts'), ['GET', ADMIN.'/eggs/3/scripts']]]],
        'eggs variables' => [['requests' => [...adminMatrix(null, ADMIN.'/eggs/1/variables'), ['GET', ADMIN.'/eggs/3/variables']]]],
        'locations index' => [['requests' => [...adminMatrix('location', ADMIN.'/locations'), ...World::pages(ADMIN.'/locations', ADMIN.'/locations?filter[short]=none')]]],
        'locations view' => [['requests' => [...adminMatrix('location', ADMIN.'/locations/1'), ['GET', ADMIN.'/locations/3?include=nodes,servers']]]],
        'locations eligible nodes' => [['requests' => [...adminMatrix('node', ADMIN.'/locations/1/eligible-nodes'), ['GET', ADMIN.'/locations/3/eligible-nodes']]]],
        'mounts index' => [['requests' => [...adminMatrix('mount', ADMIN.'/mounts'), ...World::pages(ADMIN.'/mounts', ADMIN.'/mounts?filter[name]=none')]]],
        'mounts view' => [['requests' => [...adminMatrix('mount', ADMIN.'/mounts/1'), ['GET', ADMIN.'/mounts/2?include=eggs,nodes,servers']]]],
        'nodes index' => [['requests' => [...adminMatrix('node', ADMIN.'/nodes'), ...World::pages(ADMIN.'/nodes', ADMIN.'/nodes?filter[name]=none')]]],
        'nodes view' => [['requests' => [...adminMatrix('node', ADMIN.'/nodes/1'), ['GET', ADMIN.'/nodes/2?include=allocations,location,servers']]]],
        'nodes tags' => [['requests' => [
            ['GET', ADMIN.'/nodes?include=tags,deployment_tags'],
            ['GET', ADMIN.'/nodes/1?include=tags'],
            ['GET', ADMIN.'/nodes/2?include=deployment_tags.nodes'],
        ]]],
        'nodes allocations' => [['requests' => [
            ...adminMatrix('allocation', ADMIN.'/nodes/1/allocations'),
            ...World::pages(ADMIN.'/nodes/1/allocations', ADMIN.'/nodes/1/allocations?filter[ip]=203.0.113.250'),
            ['GET', ADMIN.'/nodes/1/allocations?filter[server_id]=&include=server'],
        ]]],
        'nodes allocations available' => [['requests' => [...adminMatrix('allocation', ADMIN.'/nodes/1/allocations/available'), ...World::pages(ADMIN.'/nodes/1/allocations/available', ADMIN.'/nodes/2/allocations/available?filter[port]=1')]]],
        'servers index' => [['requests' => [...adminMatrix('server', ADMIN.'/servers'), ...World::pages(ADMIN.'/servers', ADMIN.'/servers?filter[name]=none'), ['GET', ADMIN.'/servers?filter[*]=Surv&include=user']]]],
        'servers view' => [['requests' => [...adminMatrix('server', ADMIN.'/servers/1'), ['GET', ADMIN.'/servers/5e7f0002?include=allocation,variables'], ['GET', ADMIN.'/servers/3?include=allocations,databases,subusers']]]],
        'servers external' => [['requests' => adminMatrix('server', ADMIN.'/servers/external/ext-survival')]],
        'servers backups' => [['requests' => [...adminMatrix(null, ADMIN.'/servers/1/backups'), ...World::pages(ADMIN.'/servers/1/backups', ADMIN.'/servers/3/backups')]]],
        'servers databases index' => [['requests' => [...adminMatrix('database', ADMIN.'/servers/1/databases'), ['GET', ADMIN.'/servers/3/databases']]]],
        'servers databases view' => [['requests' => adminMatrix('database', ADMIN.'/servers/1/databases/1')]],
        'servers transfer progress' => [['requests' => [...adminMatrix(null, ADMIN.'/servers/2/transfer/progress'), ['GET', ADMIN.'/servers/1/transfer/progress']]]],
        'tags index' => [['requests' => [...adminMatrix('tag', ADMIN.'/tags'), ...World::pages(ADMIN.'/tags', ADMIN.'/tags?filter[slug]=none')]]],
        'tags view' => [['requests' => [...adminMatrix('tag', ADMIN.'/tags/1'), ['GET', ADMIN.'/tags/4?include=eggs,nodes']]]],
        'users index' => [['requests' => [...adminMatrix('user', ADMIN.'/users'), ...World::pages(ADMIN.'/users', ADMIN.'/users?filter[email]=none@example.test'), ['GET', ADMIN.'/users?filter[search]=owner&include=servers']]]],
        'users view' => [['requests' => [...adminMatrix('user', ADMIN.'/users/2'), ['GET', ADMIN.'/users/5?include=servers']]]],
        'users external' => [['requests' => adminMatrix('user', ADMIN.'/users/external/ext-owner')]],
    ];
}

/**
 * Every Admin write endpoint that returns a body.
 *
 * @return array<string, array{0: array<string, mixed>}>
 */
function adminWrites(): array
{
    $node = ['name' => 'node-gamma', 'description' => 'Created by an admin', 'location_id' => 1, 'public' => true, 'fqdn' => 'gamma.example.test', 'scheme' => 'https', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'upload_size' => 100, 'daemon_listen' => 8080, 'daemon_sftp' => 2022, 'daemon_base' => '/var/lib/pterodactyl/volumes'];
    $egg = ['name' => 'Created Egg', 'description' => 'A freshly created egg.', 'docker_images' => "Java 21|ghcr.io/pterodactyl/yolks:java_21\nJava 17|ghcr.io/pterodactyl/yolks:java_17", 'startup' => 'java -jar server.jar', 'config_stop' => 'stop', 'config_startup' => '{"done": ["Done"]}', 'config_logs' => '{}', 'config_files' => '{}', 'features' => ['eula', 'fastdl']];
    $environment = ['SERVER_JARFILE' => 'server.jar', 'BUILD_NUMBER' => '42', 'HIDDEN_TOKEN' => 'abc'];
    $mount = ['name' => 'Created Mount', 'description' => 'Created by an admin.', 'source' => '/mnt/created', 'target' => '/mnt/created-target', 'read_only' => true, 'user_mountable' => false];
    $export = fn (int $id): Closure => fn (): UploadedFile => UploadedFile::fake()->createWithContent('egg.json', App::make(ExportsEggs::class)->export(Egg::query()->findOrFail($id)));

    return [
        'write api keys store' => [['requests' => [['POST', ADMIN.'/api-keys', ['memo' => 'Created key', 'r_servers' => 1, 'r_users' => 3]]]]],
        'write api keys update' => [['requests' => [['PUT', ADMIN.'/api-keys/'.World::READ_ONLY_KEY, ['memo' => 'Updated memo', 'r_servers' => 3, 'r_users' => 1]]]]],
        'write database hosts store' => [['requests' => [['POST', ADMIN.'/database-hosts', ['name' => 'Created Host', 'host' => '127.0.0.1', 'port' => 3306, 'username' => 'someuser', 'password' => 'somepassword', 'max_databases' => 5, 'node_id' => 1]]]]],
        'write database hosts update' => [['requests' => [['PUT', ADMIN.'/database-hosts/1', ['name' => 'primary-db-renamed', 'host' => '127.0.0.1', 'port' => 3306, 'username' => 'pterodactyl', 'password' => 'changed', 'max_databases' => 20, 'node_id' => 1]]]]],
        'write eggs store' => [['requests' => [['POST', ADMIN.'/eggs', $egg]]]],
        'write eggs catalog import' => [['requests' => [['POST', ADMIN.'/eggs/catalog/import', ['catalog_id' => 'games-paper']]]]],
        'write eggs import' => [['requests' => [['UPLOAD', ADMIN.'/eggs/import', ['import_file' => $export(2)]]]]],
        'write eggs import tags' => [['requests' => [['UPLOAD', ADMIN.'/eggs/import?include=tags', ['import_file' => $export(1)]]]]],
        'write eggs update' => [['requests' => [['PUT', ADMIN.'/eggs/1', [...$egg, 'name' => 'Paper Renamed', 'startup' => 'java -jar updated.jar']]]]],
        'write eggs update import' => [['requests' => [['UPLOAD', ADMIN.'/eggs/3/import', ['import_file' => $export(1)]]]]],
        'write eggs update import tags' => [['requests' => [['UPLOAD', ADMIN.'/eggs/3/import?include=tags', ['import_file' => $export(1)]]]]],
        'write eggs scripts' => [['requests' => [['PUT', ADMIN.'/eggs/1/scripts', ['script_install' => 'echo "updated"', 'script_is_privileged' => false, 'script_entry' => 'bash', 'script_container' => 'alpine:3.20']]]]],
        'write eggs tags' => [['requests' => [['PUT', ADMIN.'/eggs/1/tags', ['tags' => [2, 'Brand New Tag']]]]]],
        'write eggs variables store' => [['requests' => [['POST', ADMIN.'/eggs/1/variables', ['name' => 'Max Players', 'description' => 'How many may join.', 'env_variable' => 'MAX_PLAYERS', 'options' => ['user_viewable', 'user_editable'], 'rules' => 'required|integer|between:1,100', 'default_value' => '20']]]]],
        'write eggs variables reorder' => [['requests' => [['PUT', ADMIN.'/eggs/1/variables/reorder', ['order' => [2, 1, 3]]]]]],
        'write eggs variables update' => [['requests' => [['PUT', ADMIN.'/eggs/1/variables/1', ['name' => 'Server Jar', 'description' => 'Renamed.', 'env_variable' => 'SERVER_JARFILE', 'options' => ['user_viewable'], 'rules' => 'required|string', 'default_value' => 'server.jar']]]]],
        'write locations store' => [['requests' => [['POST', ADMIN.'/locations', ['short' => 'ap-south', 'long' => 'Asia Pacific South']]]]],
        'write locations update' => [['requests' => [['PUT', ADMIN.'/locations/1', ['short' => 'us-east-1', 'long' => 'US East 1']]]]],
        'write mounts store' => [['requests' => [['POST', ADMIN.'/mounts', $mount]]]],
        'write mounts update' => [['requests' => [['PUT', ADMIN.'/mounts/1', [...$mount, 'name' => 'shared-mods-renamed', 'user_mountable' => true]]]]],
        'write mounts attach eggs' => [['requests' => [['POST', ADMIN.'/mounts/2/eggs?include=eggs,nodes', ['eggs' => [1, 2]]]]]],
        'write mounts attach nodes' => [['requests' => [['POST', ADMIN.'/mounts/2/nodes', ['nodes' => [1]]]]]],
        'write nodes store' => [['requests' => [['POST', ADMIN.'/nodes', $node]]]],
        'write nodes update' => [['requests' => [['PUT', ADMIN.'/nodes/1', [...$node, 'name' => 'node-alpha-renamed', 'fqdn' => 'alpha.example.test', 'memory' => 16384]]]]],
        'write nodes allocations update' => [['requests' => [['PUT', ADMIN.'/nodes/1/allocations/4', ['alias' => 'renamed.example.test']]]]],
        'write nodes deployment tags' => [['requests' => [['PUT', ADMIN.'/nodes/1/deployment-tags', ['tags' => [3]]]]]],
        'write nodes tags' => [['requests' => [['PUT', ADMIN.'/nodes/1/tags', ['tags' => [1, 2]]]]]],
        'write servers store' => [['requests' => [['POST', ADMIN.'/servers', [
            'name' => 'Admin Created', 'description' => 'Created by an admin.', 'external_id' => 'ext-admin-created', 'owner_id' => World::OWNER, 'egg_id' => 1,
            'docker_image' => 'ghcr.io/pterodactyl/yolks:java_21', 'startup' => 'java -jar {{SERVER_JARFILE}}', 'environment' => $environment,
            'memory' => 512, 'swap' => 0, 'disk' => 1024, 'io' => 500, 'cpu' => 0, 'threads' => null, 'database_limit' => 0, 'allocation_limit' => 0,
            'backup_limit' => 0, 'primary_allocation_id' => 4, 'start_on_completion' => false,
        ]]]]],
        'write servers backups toggle' => [['requests' => [['POST', ADMIN.'/servers/1/backups/'.World::BACKUP_FAILED.'/toggle']]]],
        'write servers build' => [['requests' => [['PUT', ADMIN.'/servers/1/build', ['allocation_id' => 1, 'oom_disabled' => false, 'memory' => 1024, 'swap' => 0, 'io' => 500, 'cpu' => 100, 'threads' => null, 'disk' => 2048, 'database_limit' => 2, 'allocation_limit' => 3, 'backup_limit' => 4, 'add_allocation_ids' => [4], 'remove_allocation_ids' => [2]]]]]],
        'write servers databases store' => [['redact' => ['password'], 'requests' => [['POST', ADMIN.'/servers/1/databases', ['database' => 'admindb', 'remote' => '%', 'database_host_id' => 1, 'max_connections' => 25]]]]],
        'write servers databases rotate password' => [['redact' => ['password'], 'requests' => [['POST', ADMIN.'/servers/1/databases/1/rotate-password']]]],
        'write servers details' => [['requests' => [['PUT', ADMIN.'/servers/1/details', ['name' => 'Survival Admin', 'owner_id' => World::OWNER, 'external_id' => 'ext-survival-admin', 'description' => 'Updated by an admin.']]]]],
        'write servers startup' => [['requests' => [['PUT', ADMIN.'/servers/1/startup', ['startup' => 'java -jar {{SERVER_JARFILE}}', 'egg_id' => 1, 'docker_image' => 'ghcr.io/pterodactyl/yolks:java_17', 'skip_scripts' => true, 'environment' => [...$environment, 'SERVER_JARFILE' => 'renamed.jar']]]]]],
        'write tags store' => [['requests' => [['POST', ADMIN.'/tags', ['name' => 'Created Tag', 'slug' => 'created-tag', 'color' => '#123456']]]]],
        'write tags update' => [['requests' => [['PUT', ADMIN.'/tags/2', ['name' => 'Premium Plus', 'slug' => 'premium-plus', 'color' => '#654321']]]]],
        'write users store' => [['requests' => [['POST', ADMIN.'/users', ['username' => 'created', 'email' => 'created@example.test', 'name_first' => 'Cre', 'name_last' => 'Ated', 'external_id' => 'ext-created']]]]],
        'write users update' => [['requests' => [['PUT', ADMIN.'/users/2?include=servers', ['username' => 'owner', 'email' => 'owner@example.test', 'name_first' => 'Olivia', 'name_last' => 'Owner']]]]],
    ];
}

/**
 * Endpoints that call withExtensionFields(): the Admin API returns every extension's fields,
 * and lists leave them out.
 *
 * @return array<string, array{0: array<string, mixed>}>
 */
function adminExtensionFields(): array
{
    $setup = function (): void {
        $this->extensionsDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-snapshot-admin-extensions';
        File::deleteDirectory($this->extensionsDirectory);
        File::ensureDirectoryExists($this->extensionsDirectory.DIRECTORY_SEPARATOR.'notes');
        File::put($this->extensionsDirectory.DIRECTORY_SEPARATOR.'notes'.DIRECTORY_SEPARATOR.'extension.json', json_encode(['id' => 'notes', 'name' => 'Notes', 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
        config(['extensions.enabled' => true, 'extensions.directory' => $this->extensionsDirectory]);
        Extension::query()->create(['identifier' => 'notes', 'version' => '1.0.0', 'enabled' => true]);

        $models = [User::class => World::OWNER, Server::class => 1, Node::class => 1, Location::class => 1, Egg::class => 1, Mount::class => 1, DatabaseHost::class => 1];
        foreach (array_keys($models) as $model) {
            $this->app->make(ExtensionFieldRegistry::class)->register('notes', $model, SnapshotAdminNoteFields::class, $this->app->make(ExtensionRegistration::class));
        }

        $repository = $this->app->make(ExtensionRepository::class);
        $repository->flushDiscovery();
        foreach ($models as $model => $id) {
            $repository->settings('notes')->fields($model::query()->findOrFail($id))->set('note', 'Note for '.class_basename($model).' '.$id);
        }
    };

    return ['extension fields' => [['setup' => $setup, 'requests' => [
        ['GET', ADMIN.'/users/2'],
        ['GET', ADMIN.'/users/3'],
        ['GET', ADMIN.'/users/2?include=servers'],
        ['GET', ADMIN.'/servers/1?include=user,node,location,egg'],
        ['GET', ADMIN.'/servers/1'],
        ['GET', ADMIN.'/nodes/1'],
        ['GET', ADMIN.'/locations/1'],
        ['GET', ADMIN.'/eggs/1'],
        ['GET', ADMIN.'/mounts/1'],
        ['GET', ADMIN.'/database-hosts/1'],
        ['GET', ADMIN.'/users?per_page=2'],
        ['PUT', ADMIN.'/users/2', ['username' => 'owner', 'email' => 'owner@example.test', 'name_first' => 'Olive', 'name_last' => 'Owner', 'extensions' => ['notes' => ['note' => 'Changed']]]],
    ]]]];
}

test('admin api responses match their snapshots', function (array $scenario): void {
    expect(World::run($this, $scenario))->toMatchSnapshot();
})->with([...adminReads(), ...adminWrites(), ...adminExtensionFields()]);
