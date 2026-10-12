<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Parity\ResourceParityTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Pterodactyl\Http\Resources\Api\Application\ServerVariableResource;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Tests\Integration\Api\Parity\ResourceParity;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Transformers\Api\Application\ServerVariableTransformer;

/*
 * The pilot resources of each API against the transformers they replace, through the
 * parity harness: items, lists, pages, includes the requester may and may not see,
 * forced includes, meta and explicit statuses.
 */

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('application server variables render the same with forced includes, meta, a status and pages', function (): void {
    $server = $this->createServerModel();
    $admin = User::factory()->create(['root_admin' => true]);
    $key = ApiKey::factory()->create(['user_id' => $admin->id, 'key_type' => ApiKey::TYPE_ACCOUNT]);

    $one = fn () => App::make(EnvironmentService::class)->variables(Server::query()->findOrFail($server->id))->firstOrFail();
    $page = fn () => EggVariable::query()->where('egg_id', $server->egg_id)->paginate(1);

    expect(ResourceParity::item(ServerVariableTransformer::class, ServerVariableResource::class, $one)
        ->forcing('parent')
        ->withMeta(['resource' => 'https://panel.example.test/api/application/servers/1'])
        ->respond(201)
        ->actingAs($admin, $key)
        ->assertSame())->toEndWith('"meta":{"resource":"https:\/\/panel.example.test\/api\/application\/servers\/1"}}');
    expect(ResourceParity::list(ServerVariableTransformer::class, ServerVariableResource::class, $page)->request('/api/application/servers/1/variables?include=parent&page=2')->actingAs($admin, $key)->assertSame())
        ->toContain('"links":{"previous":');
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
