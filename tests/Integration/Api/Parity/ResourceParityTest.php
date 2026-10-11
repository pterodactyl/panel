<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Parity\ResourceParityTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Resources\Api\Admin\ServerDatabaseResource;
use Pterodactyl\Http\Resources\Api\Application\ServerVariableResource;
use Pterodactyl\Http\Resources\Api\Client\DatabaseResource;
use Pterodactyl\Http\Resources\Api\Client\UserSSHKeyResource;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Database;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Tests\Integration\Api\Parity\ResourceParity;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Transformers\Api\Admin\ServerDatabaseTransformer;
use Pterodactyl\Transformers\Api\Application\ServerVariableTransformer;
use Pterodactyl\Transformers\Api\Client\DatabaseTransformer;
use Pterodactyl\Transformers\Api\Client\UserSSHKeyTransformer;

/*
 * The pilot resources of each API against the transformers they replace, through the
 * parity harness: items, lists, pages, includes the requester may and may not see,
 * forced includes, meta and explicit statuses.
 */

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/**
 * @return array{0: Server, 1: list<int>}
 */
function serverWithDatabases(IntegrationTestCase $test): array
{
    $server = (fn (): Server => $this->createServerModel())->call($test);
    $host = DatabaseHost::factory()->create(['node_id' => $server->node_id]);
    $databases = Database::factory()->count(2)->create(['server_id' => $server->id, 'database_host_id' => $host->id]);

    return [$server, $databases->modelKeys()];
}

test('client ssh keys render the same', function (): void {
    $user = User::factory()->create();
    $first = UserSSHKey::factory()->create(['user_id' => $user->id]);
    UserSSHKey::factory()->dsa()->create(['user_id' => $user->id]);
    UserSSHKey::factory()->rsa()->create(['user_id' => $user->id]);

    $one = fn (): UserSSHKey => UserSSHKey::query()->findOrFail($first->id);
    $keys = fn () => UserSSHKey::query()->where('user_id', $user->id)->get();
    $page = fn () => UserSSHKey::query()->where('user_id', $user->id)->paginate(1)->appends(['sort' => 'name']);

    expect(ResourceParity::item(UserSSHKeyTransformer::class, UserSSHKeyResource::class, $one)->actingAs($user)->assertSame())
        ->toStartWith('{"object":"ssh_key","attributes":{"name":');
    expect(ResourceParity::list(UserSSHKeyTransformer::class, UserSSHKeyResource::class, $keys)->actingAs($user)->assertSame())
        ->toStartWith('{"object":"list","data":[{"object":"ssh_key"');
    expect(ResourceParity::list(UserSSHKeyTransformer::class, UserSSHKeyResource::class, $page)->request('/api/client/account/ssh-keys?page=2')->actingAs($user)->assertSame())
        ->toContain('"pagination":{"total":3,"count":1,"per_page":1,"current_page":2,"total_pages":3,"links":{"previous":');
    expect(ResourceParity::list(UserSSHKeyTransformer::class, UserSSHKeyResource::class, fn () => UserSSHKey::query()->whereKey(0)->paginate())->actingAs($user)->assertSame())
        ->toContain('"data":[],"meta":{"pagination":{"total":0,"count":0,"per_page":15,"current_page":1,"total_pages":1,"links":{}}}');
});

test('client databases render the same, with the password only for users who may see it', function (): void {
    [$server, $ids] = serverWithDatabases($this);
    $owner = User::query()->findOrFail($server->owner_id);
    $subuser = User::factory()->create();
    Subuser::query()->create(['user_id' => $subuser->id, 'server_id' => $server->id, 'permissions' => [Permissions::DatabaseRead->value]]);

    $one = fn (): Database => Database::query()->findOrFail($ids[0]);
    $all = fn () => Database::query()->where('server_id', $server->id)->get();

    expect(ResourceParity::item(DatabaseTransformer::class, DatabaseResource::class, $one)->request('/?include=password')->actingAs($owner)->assertSame())
        ->toContain('"relationships":{"password":{"object":"database_password","attributes":{"password":');
    expect(ResourceParity::item(DatabaseTransformer::class, DatabaseResource::class, $one)->request('/?include=password')->actingAs($subuser)->assertSame())
        ->toContain('"relationships":{"password":{"object":"null_resource","attributes":null}}');
    ResourceParity::item(DatabaseTransformer::class, DatabaseResource::class, $one)->forcing('password')->actingAs($owner)->assertSame();
    ResourceParity::item(DatabaseTransformer::class, DatabaseResource::class, $one)->actingAs($subuser)->assertSame();
    ResourceParity::list(DatabaseTransformer::class, DatabaseResource::class, $all)->request('/?include=password')->actingAs($owner)->assertSame();
    ResourceParity::list(DatabaseTransformer::class, DatabaseResource::class, $all)->request('/?include=password')->actingAs($subuser)->assertSame();
});

test('admin server databases render the same', function (): void {
    [$server, $ids] = serverWithDatabases($this);
    $admin = User::factory()->create(['root_admin' => true]);

    $one = fn (): Database => Database::query()->findOrFail($ids[0]);
    $all = fn () => Database::query()->where('server_id', $server->id)->get();
    $page = fn () => Database::query()->where('server_id', $server->id)->paginate(1);

    expect(ResourceParity::item(ServerDatabaseTransformer::class, ServerDatabaseResource::class, $one)->actingAs($admin)->assertSame())
        ->toEndWith('"relationships":[]}}');
    ResourceParity::item(ServerDatabaseTransformer::class, ServerDatabaseResource::class, $one)->request('/?include=password')->actingAs($admin)->assertSame();
    expect(ResourceParity::item(ServerDatabaseTransformer::class, ServerDatabaseResource::class, $one)
        ->forcing('password')
        ->withMeta(['resource' => 'https://panel.example.test/api/admin/servers/1/databases/1'])
        ->respond(201)
        ->actingAs($admin)
        ->assertSame())->toEndWith('"meta":{"resource":"https:\/\/panel.example.test\/api\/admin\/servers\/1\/databases\/1"}}');
    ResourceParity::list(ServerDatabaseTransformer::class, ServerDatabaseResource::class, $all)->actingAs($admin)->assertSame();
    ResourceParity::list(ServerDatabaseTransformer::class, ServerDatabaseResource::class, $page)->request('/api/admin/servers/1/databases?include=password')->actingAs($admin)->assertSame();
});

test('application server variables render the same for keys with and without egg access', function (int $type, int $eggs, string $parent): void {
    $server = $this->createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);
    $key = ApiKey::factory()->create(['user_id' => $admin->id, 'key_type' => $type, AdminAcl::COLUMN_IDENTIFIER.AdminAcl::RESOURCE_EGGS => $eggs]);

    $variables = fn () => App::make(EnvironmentService::class)->variables(Server::query()->findOrFail($server->id));

    expect(ResourceParity::list(ServerVariableTransformer::class, ServerVariableResource::class, $variables)->request('/?include=parent')->actingAs($admin, $key)->assertSame())
        ->toContain('"relationships":{"parent":{"object":'.$parent);
    ResourceParity::item(ServerVariableTransformer::class, ServerVariableResource::class, fn () => $variables()->firstOrFail())->request('/?include=parent')->actingAs($admin, $key)->assertSame();
    ResourceParity::item(ServerVariableTransformer::class, ServerVariableResource::class, fn () => $variables()->firstOrFail())->actingAs($admin, $key)->assertSame();
})->with([
    'an application key that reads eggs' => [ApiKey::TYPE_APPLICATION, AdminAcl::READ, '"variable"'],
    'an application key without eggs' => [ApiKey::TYPE_APPLICATION, AdminAcl::NONE, '"null_resource"'],
    'a root admin account key' => [ApiKey::TYPE_ACCOUNT, AdminAcl::NONE, '"variable"'],
]);
