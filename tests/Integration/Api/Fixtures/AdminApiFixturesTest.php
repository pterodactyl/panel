<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Fixtures\AdminApiFixturesTest;

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
use Pterodactyl\Tests\Integration\Api\Fixtures\ApiFixtureWorld as World;

uses(AdminApiIntegrationTestCase::class);

final class NoteFields extends Fields
{
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

const ADMIN_INCLUDES = [
    'database_host' => ['node', 'databases'],
    'egg' => ['variables', 'tags', 'servers'],
    'location' => ['nodes', 'servers'],
    'mount' => ['eggs', 'nodes', 'servers'],
    'node' => ['allocations', 'location', 'servers'],
    'allocation' => ['node', 'server'],
    'server' => ['allocation', 'allocations', 'user', 'subusers', 'egg', 'variables', 'location', 'node', 'databases'],
    'tag' => ['eggs', 'nodes'],
    'user' => ['servers'],
];

function adminReads(): array
{
    return [
        'admin/activity.index' => ['requests' => [
            ...World::eachInclude(ADMIN.'/activity', ['actor'], 'actor.servers'),
            ...World::pages(ADMIN.'/activity', ADMIN.'/activity?filter[event]=admin:nothing'),
            ['GET', ADMIN.'/activity?filter[event]=admin:&sort=timestamp&per_page=1&page=2&include=actor'],
        ]],
        'admin/api-keys.index' => ['requests' => [['GET', ADMIN.'/api-keys'], ...World::pages(ADMIN.'/api-keys', ADMIN.'/api-keys?filter[memo]=none')]],
        'admin/api-keys.view' => ['requests' => [['GET', ADMIN.'/api-keys/'.World::READ_ONLY_KEY], ['GET', ADMIN.'/api-keys/'.World::APPLICATION_KEY]]],
        'admin/database-hosts.index' => ['requests' => [
            ...World::allIncludes(ADMIN.'/database-hosts', ADMIN_INCLUDES['database_host']),
            ...World::pages(ADMIN.'/database-hosts', ADMIN.'/database-hosts?filter[name]=none'),
        ]],
        'admin/database-hosts.view' => ['requests' => [
            ...World::eachInclude(ADMIN.'/database-hosts/1', ADMIN_INCLUDES['database_host'], 'node.location'),
            ['GET', ADMIN.'/database-hosts/2?include=node,databases'],
        ]],
        'admin/database-hosts/databases.index' => ['requests' => [
            ['GET', ADMIN.'/database-hosts/1/databases'],
            ...World::pages(ADMIN.'/database-hosts/1/databases', ADMIN.'/database-hosts/2/databases'),
        ]],
        'admin/eggs.index' => ['requests' => [
            ...World::allIncludes(ADMIN.'/eggs', ADMIN_INCLUDES['egg']),
            ...World::pages(ADMIN.'/eggs', ADMIN.'/eggs?filter[name]=no-such-egg'),
            ['GET', ADMIN.'/eggs?filter[tag]=premium'],
            ['GET', ADMIN.'/eggs?filter[tag]=minecraft,premium&include=tags'],
            ['GET', ADMIN.'/eggs?filter[tag]=none'],
        ]],
        'admin/eggs.view' => ['requests' => [
            ...World::eachInclude(ADMIN.'/eggs/1', ADMIN_INCLUDES['egg'], 'servers.user'),
            ['GET', ADMIN.'/eggs/2'],
            ['GET', ADMIN.'/eggs/3?include=variables,tags,servers'],
        ]],
        'admin/eggs/scripts.view' => ['requests' => [...World::allIncludes(ADMIN.'/eggs/2/scripts', ADMIN_INCLUDES['egg']), ['GET', ADMIN.'/eggs/3/scripts']]],
        'admin/eggs/variables.index' => ['requests' => [['GET', ADMIN.'/eggs/1/variables'], ['GET', ADMIN.'/eggs/3/variables']]],
        'admin/locations.index' => ['requests' => [
            ...World::allIncludes(ADMIN.'/locations', ADMIN_INCLUDES['location']),
            ...World::pages(ADMIN.'/locations', ADMIN.'/locations?filter[short]=none'),
        ]],
        'admin/locations.view' => ['requests' => [
            ...World::eachInclude(ADMIN.'/locations/1', ADMIN_INCLUDES['location'], 'nodes.allocations'),
            ['GET', ADMIN.'/locations/3?include=nodes,servers'],
        ]],
        'admin/locations/eligible-nodes.index' => ['requests' => [
            ...World::allIncludes(ADMIN.'/locations/1/eligible-nodes', ADMIN_INCLUDES['node']),
            ['GET', ADMIN.'/locations/3/eligible-nodes'],
        ]],
        'admin/mounts.index' => ['requests' => [
            ...World::allIncludes(ADMIN.'/mounts', ADMIN_INCLUDES['mount']),
            ...World::pages(ADMIN.'/mounts', ADMIN.'/mounts?filter[name]=none'),
        ]],
        'admin/mounts.view' => ['requests' => [
            ...World::eachInclude(ADMIN.'/mounts/1', ADMIN_INCLUDES['mount'], 'eggs.tags'),
            ['GET', ADMIN.'/mounts/2?include=eggs,nodes,servers'],
        ]],
        'admin/nodes.index' => ['requests' => [
            ...World::allIncludes(ADMIN.'/nodes', ADMIN_INCLUDES['node']),
            ['GET', ADMIN.'/nodes?include=tags,deployment_tags'],
            ...World::pages(ADMIN.'/nodes', ADMIN.'/nodes?filter[name]=none'),
        ]],
        'admin/nodes.view' => ['requests' => [
            ...World::eachInclude(ADMIN.'/nodes/1', ADMIN_INCLUDES['node'], 'allocations.server'),
            ['GET', ADMIN.'/nodes/1?include=tags'],
            ['GET', ADMIN.'/nodes/2?include=allocations,location,servers'],
            ['GET', ADMIN.'/nodes/2?include=deployment_tags.nodes'],
        ]],
        'admin/nodes/allocations.index' => ['requests' => [
            ...World::eachInclude(ADMIN.'/nodes/1/allocations', ADMIN_INCLUDES['allocation'], 'server.user'),
            ...World::pages(ADMIN.'/nodes/1/allocations', ADMIN.'/nodes/1/allocations?filter[ip]=203.0.113.250'),
            ['GET', ADMIN.'/nodes/1/allocations?filter[server_id]=&include=server'],
        ]],
        'admin/nodes/allocations/available.index' => ['requests' => [
            ...World::allIncludes(ADMIN.'/nodes/1/allocations/available', ADMIN_INCLUDES['allocation']),
            ...World::pages(ADMIN.'/nodes/1/allocations/available', ADMIN.'/nodes/2/allocations/available?filter[port]=1'),
        ]],
        'admin/servers.index' => ['requests' => [
            ...World::allIncludes(ADMIN.'/servers', ADMIN_INCLUDES['server']),
            ...World::pages(ADMIN.'/servers', ADMIN.'/servers?filter[name]=none'),
            ['GET', ADMIN.'/servers?filter[*]=Surv&include=user'],
        ]],
        'admin/servers.view' => ['requests' => [
            ...World::eachInclude(ADMIN.'/servers/1', ADMIN_INCLUDES['server'], 'egg.variables,node.location,databases.password'),
            ['GET', ADMIN.'/servers/5e7f0002?include=allocation,variables'],
            ['GET', ADMIN.'/servers/3?include=allocations,databases,subusers'],
        ]],
        'admin/servers/external.view' => ['requests' => World::allIncludes(ADMIN.'/servers/external/ext-survival', ADMIN_INCLUDES['server'])],
        'admin/servers/backups.index' => ['requests' => [
            ['GET', ADMIN.'/servers/1/backups'],
            ...World::pages(ADMIN.'/servers/1/backups', ADMIN.'/servers/3/backups'),
        ]],
        'admin/servers/databases.index' => ['requests' => [...World::allIncludes(ADMIN.'/servers/1/databases', ['password']), ['GET', ADMIN.'/servers/3/databases']]],
        'admin/servers/databases.view' => ['requests' => World::eachInclude(ADMIN.'/servers/1/databases/1', ['password'])],
        'admin/servers/transfer/progress.view' => ['requests' => [['GET', ADMIN.'/servers/2/transfer/progress'], ['GET', ADMIN.'/servers/1/transfer/progress']]],
        'admin/tags.index' => ['requests' => [
            ...World::allIncludes(ADMIN.'/tags', ADMIN_INCLUDES['tag']),
            ...World::pages(ADMIN.'/tags', ADMIN.'/tags?filter[slug]=none'),
        ]],
        'admin/tags.view' => ['requests' => [
            ...World::eachInclude(ADMIN.'/tags/1', ADMIN_INCLUDES['tag'], 'eggs.variables'),
            ['GET', ADMIN.'/tags/4?include=eggs,nodes'],
        ]],
        'admin/users.index' => ['requests' => [
            ...World::allIncludes(ADMIN.'/users', ADMIN_INCLUDES['user']),
            ...World::pages(ADMIN.'/users', ADMIN.'/users?filter[email]=none@example.test'),
            ['GET', ADMIN.'/users?filter[search]=owner&include=servers'],
        ]],
        'admin/users.view' => ['requests' => [
            ...World::eachInclude(ADMIN.'/users/2', ADMIN_INCLUDES['user'], 'servers.node'),
            ['GET', ADMIN.'/users/2?include=does-not-exist'],
            ['GET', ADMIN.'/users/5?include=servers'],
        ]],
        'admin/users/external.view' => ['requests' => World::allIncludes(ADMIN.'/users/external/ext-owner', ADMIN_INCLUDES['user'])],
    ];
}

function adminWrites(): array
{
    $node = ['name' => 'node-gamma', 'description' => 'Created by an admin', 'location_id' => 1, 'public' => true, 'fqdn' => 'gamma.example.test', 'scheme' => 'https', 'behind_proxy' => false, 'maintenance_mode' => false, 'memory' => 2048, 'memory_overallocate' => 0, 'disk' => 20480, 'disk_overallocate' => 0, 'upload_size' => 100, 'daemon_listen' => 8080, 'daemon_sftp' => 2022, 'daemon_base' => '/var/lib/pterodactyl/volumes'];
    $egg = ['name' => 'Created Egg', 'description' => 'A freshly created egg.', 'docker_images' => "Java 21|ghcr.io/pterodactyl/yolks:java_21\nJava 17|ghcr.io/pterodactyl/yolks:java_17", 'startup' => 'java -jar server.jar', 'config_stop' => 'stop', 'config_startup' => '{"done": ["Done"]}', 'config_logs' => '{}', 'config_files' => '{}', 'features' => ['eula', 'fastdl']];
    $environment = ['SERVER_JARFILE' => 'server.jar', 'BUILD_NUMBER' => '42', 'HIDDEN_TOKEN' => 'abc'];
    $server = [
        'name' => 'Admin Created', 'description' => 'Created by an admin.', 'external_id' => 'ext-admin-created', 'owner_id' => World::OWNER, 'egg_id' => 1,
        'docker_image' => 'ghcr.io/pterodactyl/yolks:java_21', 'startup' => 'java -jar {{SERVER_JARFILE}}', 'environment' => $environment,
        'memory' => 512, 'swap' => 0, 'disk' => 1024, 'io' => 500, 'cpu' => 0, 'threads' => null, 'database_limit' => 0, 'allocation_limit' => 0,
        'backup_limit' => 0, 'primary_allocation_id' => 4, 'start_on_completion' => false,
    ];
    $startup = ['startup' => 'java -jar {{SERVER_JARFILE}}', 'egg_id' => 1, 'docker_image' => 'ghcr.io/pterodactyl/yolks:java_17', 'skip_scripts' => true, 'environment' => [...$environment, 'SERVER_JARFILE' => 'renamed.jar']];
    $mount = ['name' => 'Created Mount', 'description' => 'Created by an admin.', 'source' => '/mnt/created', 'target' => '/mnt/created-target', 'read_only' => true, 'user_mountable' => false];
    $export = fn (int $id): Closure => fn (): UploadedFile => UploadedFile::fake()->createWithContent('egg.json', App::make(ExportsEggs::class)->export(Egg::query()->findOrFail($id)));

    return [
        'admin/api-keys.store' => ['requests' => [['POST', ADMIN.'/api-keys', ['memo' => 'Created key', 'r_servers' => 1, 'r_users' => 3]]]],
        'admin/api-keys.update' => ['requests' => [['PUT', ADMIN.'/api-keys/'.World::READ_ONLY_KEY, ['memo' => 'Updated memo', 'r_servers' => 3, 'r_users' => 1]]]],
        'admin/database-hosts.store' => ['requests' => [['POST', ADMIN.'/database-hosts', ['name' => 'Created Host', 'host' => '127.0.0.1', 'port' => 3306, 'username' => 'someuser', 'password' => 'somepassword', 'max_databases' => 5, 'node_id' => 1]]]],
        'admin/database-hosts.update' => ['requests' => [['PUT', ADMIN.'/database-hosts/1', ['name' => 'primary-db-renamed', 'host' => '127.0.0.1', 'port' => 3306, 'username' => 'pterodactyl', 'password' => 'changed', 'max_databases' => 20, 'node_id' => 1]]]],
        'admin/eggs.store' => ['requests' => [['POST', ADMIN.'/eggs', $egg]]],
        'admin/eggs.update' => ['requests' => [['PUT', ADMIN.'/eggs/1', [...$egg, 'name' => 'Paper Renamed', 'startup' => 'java -jar updated.jar']]]],
        'admin/eggs/catalog/import.store' => ['requests' => [['POST', ADMIN.'/eggs/catalog/import', ['catalog_id' => 'games-paper']]]],
        'admin/eggs/import.store' => ['requests' => [['POST', ADMIN.'/eggs/import', ['import_file' => $export(2)]]]],
        'admin/eggs/import.store.tags' => ['requests' => [['POST', ADMIN.'/eggs/import?include=tags', ['import_file' => $export(1)]]]],
        'admin/eggs/import.update' => ['requests' => [['POST', ADMIN.'/eggs/3/import', ['import_file' => $export(1)]]]],
        'admin/eggs/import.update.tags' => ['requests' => [['POST', ADMIN.'/eggs/3/import?include=tags', ['import_file' => $export(1)]]]],
        'admin/eggs/scripts.update' => ['requests' => [['PUT', ADMIN.'/eggs/1/scripts', ['script_install' => 'echo "updated"', 'script_is_privileged' => false, 'script_entry' => 'bash', 'script_container' => 'alpine:3.20']]]],
        'admin/eggs/tags.update' => ['requests' => [['PUT', ADMIN.'/eggs/1/tags', ['tags' => [2, 'Brand New Tag']]]]],
        'admin/eggs/variables.store' => ['requests' => [['POST', ADMIN.'/eggs/1/variables', ['name' => 'Max Players', 'description' => 'How many may join.', 'env_variable' => 'MAX_PLAYERS', 'options' => ['user_viewable', 'user_editable'], 'rules' => 'required|integer|between:1,100', 'default_value' => '20']]]],
        'admin/eggs/variables.reorder' => ['requests' => [['PUT', ADMIN.'/eggs/1/variables/reorder', ['order' => [2, 1, 3]]]]],
        'admin/eggs/variables.update' => ['requests' => [['PUT', ADMIN.'/eggs/1/variables/1', ['name' => 'Server Jar', 'description' => 'Renamed.', 'env_variable' => 'SERVER_JARFILE', 'options' => ['user_viewable'], 'rules' => 'required|string', 'default_value' => 'server.jar']]]],
        'admin/locations.store' => ['requests' => [['POST', ADMIN.'/locations', ['short' => 'ap-south', 'long' => 'Asia Pacific South']]]],
        'admin/locations.update' => ['requests' => [['PUT', ADMIN.'/locations/1', ['short' => 'us-east-1', 'long' => 'US East 1']]]],
        'admin/mounts.store' => ['requests' => [['POST', ADMIN.'/mounts', $mount]]],
        'admin/mounts.update' => ['requests' => [['PUT', ADMIN.'/mounts/1', [...$mount, 'name' => 'shared-mods-renamed', 'user_mountable' => true]]]],
        'admin/mounts/eggs.attach' => ['requests' => [['POST', ADMIN.'/mounts/2/eggs?include=eggs,nodes', ['eggs' => [1, 2]]]]],
        'admin/mounts/nodes.attach' => ['requests' => [['POST', ADMIN.'/mounts/2/nodes', ['nodes' => [1]]]]],
        'admin/nodes.store' => ['requests' => [['POST', ADMIN.'/nodes', $node]]],
        'admin/nodes.update' => ['requests' => [['PUT', ADMIN.'/nodes/1', [...$node, 'name' => 'node-alpha-renamed', 'fqdn' => 'alpha.example.test', 'memory' => 16384]]]],
        'admin/nodes/allocations.update' => ['requests' => [['PUT', ADMIN.'/nodes/1/allocations/4', ['alias' => 'renamed.example.test']]]],
        'admin/nodes/deployment-tags.update' => ['requests' => [['PUT', ADMIN.'/nodes/1/deployment-tags', ['tags' => [3]]]]],
        'admin/nodes/tags.update' => ['requests' => [['PUT', ADMIN.'/nodes/1/tags', ['tags' => [1, 2]]]]],
        'admin/servers.store' => ['requests' => [['POST', ADMIN.'/servers', $server]]],
        'admin/servers.store.tag-check' => ['requests' => [
            ['POST', ADMIN.'/servers', [...$server, 'egg_id' => 3]],
            ['POST', ADMIN.'/servers', [...$server, 'egg_id' => 3, 'ignore_tags' => true]],
        ]],
        'admin/servers/backups.toggle' => ['requests' => [['POST', ADMIN.'/servers/1/backups/'.World::BACKUP_FAILED.'/toggle']]],
        'admin/servers/build.update' => ['requests' => [['PUT', ADMIN.'/servers/1/build', ['allocation_id' => 1, 'oom_disabled' => false, 'memory' => 1024, 'swap' => 0, 'io' => 500, 'cpu' => 100, 'threads' => null, 'disk' => 2048, 'database_limit' => 2, 'allocation_limit' => 3, 'backup_limit' => 4, 'add_allocation_ids' => [4], 'remove_allocation_ids' => [2]]]]],
        'admin/servers/databases.store' => ['redact' => ['password'], 'requests' => [['POST', ADMIN.'/servers/1/databases', ['database' => 'admindb', 'remote' => '%', 'database_host_id' => 1, 'max_connections' => 25]]]],
        'admin/servers/databases.rotate-password' => ['redact' => ['password'], 'requests' => [['POST', ADMIN.'/servers/1/databases/1/rotate-password']]],
        'admin/servers/details.update' => ['requests' => [['PUT', ADMIN.'/servers/1/details', ['name' => 'Survival Admin', 'owner_id' => World::OWNER, 'external_id' => 'ext-survival-admin', 'description' => 'Updated by an admin.']]]],
        'admin/servers/startup.update' => ['requests' => [['PUT', ADMIN.'/servers/1/startup', $startup]]],
        'admin/servers/startup.update.tag-check' => ['requests' => [
            ['PUT', ADMIN.'/servers/1/startup', [...$startup, 'egg_id' => 3]],
            ['PUT', ADMIN.'/servers/1/startup', [...$startup, 'egg_id' => 3, 'ignore_tags' => true]],
        ]],
        'admin/tags.store' => ['requests' => [['POST', ADMIN.'/tags', ['name' => 'Created Tag', 'slug' => 'created-tag', 'color' => '#123456']]]],
        'admin/tags.update' => ['requests' => [['PUT', ADMIN.'/tags/2', ['name' => 'Premium Plus', 'slug' => 'premium-plus', 'color' => '#654321']]]],
        'admin/tags.update.built-in-slug' => ['requests' => [['PUT', ADMIN.'/tags/2', ['name' => 'Rust', 'slug' => 'rust', 'color' => '#654321']]]],
        'admin/users.store' => ['requests' => [['POST', ADMIN.'/users', ['username' => 'created', 'email' => 'created@example.test', 'name_first' => 'Cre', 'name_last' => 'Ated', 'external_id' => 'ext-created']]]],
        'admin/users.update' => ['requests' => [['PUT', ADMIN.'/users/2?include=servers', ['username' => 'owner', 'email' => 'owner@example.test', 'name_first' => 'Olivia', 'name_last' => 'Owner']]]],
    ];
}

/** The Admin API returns every extension's fields on the endpoints that render them, and lists leave them out. */
function adminExtensionFields(): array
{
    $setup = function (): void {
        $this->extensionsDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ptero-fixture-admin-extensions';
        File::deleteDirectory($this->extensionsDirectory);
        File::ensureDirectoryExists($this->extensionsDirectory.DIRECTORY_SEPARATOR.'notes');
        File::put($this->extensionsDirectory.DIRECTORY_SEPARATOR.'notes'.DIRECTORY_SEPARATOR.'extension.json', json_encode(['id' => 'notes', 'name' => 'Notes', 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
        config(['extensions.enabled' => true, 'extensions.directory' => $this->extensionsDirectory]);
        Extension::query()->create(['identifier' => 'notes', 'version' => '1.0.0', 'enabled' => true]);

        $models = [User::class => World::OWNER, Server::class => 1, Node::class => 1, Location::class => 1, Egg::class => 1, Mount::class => 1, DatabaseHost::class => 1];
        foreach (array_keys($models) as $model) {
            $this->app->make(ExtensionFieldRegistry::class)->register('notes', $model, NoteFields::class, $this->app->make(ExtensionRegistration::class));
        }

        $repository = $this->app->make(ExtensionRepository::class);
        $repository->flushDiscovery();
        foreach ($models as $model => $id) {
            $repository->settings('notes')->fields($model::query()->findOrFail($id))->set('note', 'Note for '.class_basename($model).' '.$id);
        }
    };

    return ['admin/extension-fields' => ['setup' => $setup, 'requests' => [
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
    ]]];
}

function adminScenarios(): array
{
    return [...adminReads(), ...adminWrites(), ...adminExtensionFields()];
}

test('admin api responses match their fixtures', function (string $fixture): void {
    expect(World::run($this, adminScenarios()[$fixture]))->toMatchApiFixture($fixture);
})->with(array_keys(adminScenarios()));
