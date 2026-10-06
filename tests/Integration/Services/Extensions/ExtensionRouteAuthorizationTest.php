<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Services\Extensions\ExtensionRouteAuthorizationTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Pterodactyl\Extensions\ExtensionProvider;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Extension;
use Pterodactyl\Models\Subuser;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Services\Extensions\ExtensionManager;
use Pterodactyl\Services\Extensions\ExtensionProviderLoader;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->extensionDirectory = sys_get_temp_dir().'/ptero-route-authorization-'.uniqid();
    File::ensureDirectoryExists($this->extensionDirectory.'/probe/routes');
    config([
        'extensions.enabled' => true,
        'extensions.directory' => $this->extensionDirectory,
        'extensions.assets_directory' => $this->extensionDirectory.'/assets',
    ]);
    $this->app->forgetInstance(ExtensionRepository::class);
    $this->app->forgetInstance(ExtensionProviderLoader::class);
    $this->app->forgetInstance(ExtensionManager::class);

    File::put($this->extensionDirectory.'/probe/extension.json', json_encode([
        'id' => 'probe',
        'name' => 'Probe',
        'version' => '1.0.0',
        'provider' => RouteAuthorizationProvider::class,
    ], JSON_THROW_ON_ERROR));
    File::put($this->extensionDirectory.'/probe/routes/application.php', <<<'PHP'
    <?php

    use Illuminate\Support\Facades\Route;

    Route::get('/report', fn (): string => 'read');
    Route::post('/report', fn (): string => 'written');
    PHP);
    File::put($this->extensionDirectory.'/probe/routes/client.php', <<<'PHP'
    <?php

    use Illuminate\Support\Facades\Route;
    use Pterodactyl\Models\Server;

    Route::get('/status', fn (): string => 'ready');
    Route::get('/servers/{server}/status', fn (Server $server): string => $server->uuid);
    PHP);
    Extension::query()->create(['identifier' => 'probe', 'version' => '1.0.0', 'enabled' => true]);

    $this->app->make(ExtensionProviderLoader::class)->registerProviders($this->app->make(ExtensionRepository::class)->enabled());
});

afterEach(function (): void {
    File::deleteDirectory($this->extensionDirectory);
});

test('application api keys need a scope on every resource to use extension routes', function (array $permissions, int $read, int $write): void {
    $key = ApiKey::factory()->create([
        ...array_fill_keys(resourceColumns(), AdminAcl::READ | AdminAcl::WRITE),
        ...$permissions,
        'user_id' => User::factory()->create(['root_admin' => true])->id,
        'key_type' => ApiKey::TYPE_APPLICATION,
    ]);
    $headers = ['Authorization' => 'Bearer '.$key->identifier.decrypt($key->token)];

    $this->getJson('/api/application/extensions/probe/report', $headers)->assertStatus($read);
    $this->postJson('/api/application/extensions/probe/report', [], $headers)->assertStatus($write);
})->with([
    'read and write on every resource' => [[], 200, 200],
    'read on every resource' => [array_fill_keys(resourceColumns(), AdminAcl::READ), 200, 403],
    'read only on one resource' => [['r_eggs' => AdminAcl::READ], 200, 403],
    'no access to one resource' => [['r_server_databases' => AdminAcl::NONE], 403, 403],
    'no access to any resource' => [array_fill_keys(resourceColumns(), AdminAcl::NONE), 403, 403],
]);

test('root admins pass extension application routes with the panel session', function (): void {
    $this->actingAs(User::factory()->create(['root_admin' => true]))
        ->postJson('/api/application/extensions/probe/report')
        ->assertOk()
        ->assertContent('written');
});

test('root admins pass extension application routes with an account api key', function (): void {
    $key = ApiKey::factory()->create([
        ...array_fill_keys(resourceColumns(), AdminAcl::NONE),
        'user_id' => User::factory()->create(['root_admin' => true])->id,
        'key_type' => ApiKey::TYPE_ACCOUNT,
        'identifier' => ApiKey::generateTokenIdentifier(ApiKey::TYPE_ACCOUNT),
    ]);

    $this->postJson('/api/application/extensions/probe/report', [], ['Authorization' => 'Bearer '.$key->identifier.decrypt($key->token)])
        ->assertOk()
        ->assertContent('written');
});

test('client extension routes only bind servers the user can access', function (string $role, int $status): void {
    [$owner, $server] = $this->generateTestAccount();
    $user = match ($role) {
        'owner' => $owner,
        'admin' => User::factory()->create(['root_admin' => true]),
        default => User::factory()->create(),
    };
    if ($role === 'subuser') {
        Subuser::query()->create(['user_id' => $user->id, 'server_id' => $server->id, 'permissions' => []]);
    }

    $response = $this->actingAs($user)->getJson("/api/client/extensions/probe/servers/$server->uuid/status")->assertStatus($status);
    if ($status === 200) {
        $response->assertContent($server->uuid);
    }

    $this->getJson('/api/client/extensions/probe/status')->assertOk()->assertContent('ready');
})->with([
    'owner' => ['owner', 200],
    'subuser' => ['subuser', 200],
    'root admin' => ['admin', 200],
    'unrelated user' => ['stranger', 404],
]);

/** @return list<string> */
function resourceColumns(): array
{
    return array_map(fn (string $resource): string => AdminAcl::COLUMN_IDENTIFIER.$resource, AdminAcl::getResourceList());
}

final class RouteAuthorizationProvider extends ExtensionProvider
{
    public function boot(): void
    {
        $this->registerApiRoutes();
    }
}
