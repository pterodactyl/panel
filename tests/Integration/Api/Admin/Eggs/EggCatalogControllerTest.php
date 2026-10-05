<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Eggs\EggCatalogControllerTest;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Pterodactyl\Contracts\Eggs\ExportsEggs;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Eggs\EggCatalogService;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
    app(EggCatalogService::class)->refresh();
});

/** @return array{id: string, name: string, description: string, category: string, downloadUrl: string} */
function catalogEntry(string $url = 'https://raw.githubusercontent.com/pterodactyl/game-eggs/main/paper.json'): array
{
    return [
        'id' => 'games-paper',
        'name' => 'Paper',
        'description' => 'A Minecraft server',
        'category' => 'games',
        'downloadUrl' => $url,
    ];
}

test('lists the official catalog and caches it until refreshed', function (): void {
    Http::fake([
        EggCatalogService::INDEX_URL => Http::sequence()->push([catalogEntry()])->push([]),
    ]);

    $this->getJson(route('api.admin.eggs.catalog'))->assertOk()->assertExactJson([
        'data' => [[
            'id' => 'games-paper',
            'name' => 'Paper',
            'description' => 'A Minecraft server',
            'category' => 'games',
            'source_url' => 'https://eggs.pterodactyl.io/egg/games-paper',
        ]],
    ]);
    $this->getJson(route('api.admin.eggs.catalog'))->assertOk()->assertJsonCount(1, 'data');
    $this->postJson(route('api.admin.eggs.catalog.refresh'))->assertNoContent();
    $this->getJson(route('api.admin.eggs.catalog'))->assertOk()->assertExactJson(['data' => []]);

    Http::assertSentCount(2);
});

test('imports a selected catalog egg with its variables and logs the import', function (): void {
    $source = Egg::query()->where('name', 'Bungeecord')->firstOrFail();
    $payload = app(ExportsEggs::class)->export($source);
    $before = Egg::query()->count();
    Http::fake([
        EggCatalogService::INDEX_URL => Http::response([catalogEntry()]),
        catalogEntry()['downloadUrl'] => Http::response($payload),
    ]);

    $response = $this->postJson(route('api.admin.eggs.catalog.import'), ['catalog_id' => 'games-paper']);

    $response->assertCreated()->assertJsonPath('attributes.name', $source->name);
    $imported = Egg::query()->findOrFail($response->json('attributes.id'));
    expect($imported->id)->not->toBe($source->id);
    expect($imported->variables()->count())->toBe($source->variables()->count());
    expect($imported->startup)->toBe($source->startup);
    expect(Egg::query()->count())->toBe($before + 1);
    $this->assertDatabaseHas('activity_logs', ['event' => 'admin:egg.import']);
    Http::assertSentCount(2);
});

test('rejects an unknown catalog id with 404 without fetching an arbitrary URL', function (): void {
    $before = Egg::query()->count();
    Http::fake([EggCatalogService::INDEX_URL => Http::response([catalogEntry()])]);

    $this->postJson(route('api.admin.eggs.catalog.import'), [
        'catalog_id' => 'https://example.com/egg.json',
        'url' => 'https://example.com/egg.json',
    ])->assertNotFound();

    expect(Egg::query()->count())->toBe($before);
    Http::assertSentCount(1);
});

test('rejects non official catalog downloads with 502', function (string $url): void {
    Http::fake([EggCatalogService::INDEX_URL => Http::response([catalogEntry($url)])]);

    $this->postJson(route('api.admin.eggs.catalog.import'), ['catalog_id' => 'games-paper'])
        ->assertStatus(502)
        ->assertJsonPath('errors.0.detail', 'The catalog egg does not have an official Pterodactyl download URL.');

    Http::assertSentCount(1);
})->with([
    'private host' => 'http://127.0.0.1/egg.json',
    'another catalog' => 'https://example.com/egg.json',
    'another GitHub owner' => 'https://raw.githubusercontent.com/other/eggs/main/egg.json',
    'credentials' => 'https://user:password@raw.githubusercontent.com/pterodactyl/game-eggs/main/egg.json',
]);

test('encodes spaces in official download paths', function (): void {
    $source = Egg::query()->where('name', 'Bungeecord')->firstOrFail();
    Http::fake([
        EggCatalogService::INDEX_URL => Http::response([catalogEntry('https://raw.githubusercontent.com/pterodactyl/game-eggs/main/My Game/egg.json')]),
        'https://raw.githubusercontent.com/pterodactyl/game-eggs/main/My%20Game/egg.json' => Http::response(app(ExportsEggs::class)->export($source)),
    ]);

    $this->postJson(route('api.admin.eggs.catalog.import'), ['catalog_id' => 'games-paper'])->assertCreated();

    Http::assertSentCount(2);
});

test('reports a catalog connection failure with 502 and permits retry', function (): void {
    Http::fake([EggCatalogService::INDEX_URL => Http::sequence()->pushFailedConnection()->push([catalogEntry()])]);

    $this->getJson(route('api.admin.eggs.catalog'))->assertStatus(502)
        ->assertJsonPath('errors.0.detail', 'Could not reach the Pterodactyl egg catalog. Please try again.');

    $this->getJson(route('api.admin.eggs.catalog'))->assertOk()->assertJsonCount(1, 'data');
    Http::assertSentCount(2);
});

test('rejects malformed catalog responses with 502 without caching them', function (string $payload): void {
    Http::fake([EggCatalogService::INDEX_URL => Http::sequence()->push($payload)->push([catalogEntry()])]);

    $this->getJson(route('api.admin.eggs.catalog'))->assertStatus(502);
    $this->getJson(route('api.admin.eggs.catalog'))->assertOk()->assertJsonCount(1, 'data');

    Http::assertSentCount(2);
})->with(['not JSON' => '<html>Error</html>', 'scalar' => 'false', 'wrong shape' => '{"error":"oops"}', 'no valid entries' => '[{"invalid":true}]']);

test('does not follow redirects from egg downloads', function (): void {
    $before = Egg::query()->count();
    Http::fake([
        EggCatalogService::INDEX_URL => Http::response([catalogEntry()]),
        catalogEntry()['downloadUrl'] => Http::response('', 302, ['Location' => 'http://127.0.0.1/egg.json']),
    ]);

    $this->postJson(route('api.admin.eggs.catalog.import'), ['catalog_id' => 'games-paper'])->assertStatus(502);

    expect(Egg::query()->count())->toBe($before);
    Http::assertSentCount(2);
});

test('does not create an egg from an invalid remote definition', function (): void {
    $before = Egg::query()->count();
    Http::fake([
        EggCatalogService::INDEX_URL => Http::response([catalogEntry()]),
        catalogEntry()['downloadUrl'] => Http::response(['meta' => ['version' => 'unsupported']]),
    ]);

    $this->postJson(route('api.admin.eggs.catalog.import'), ['catalog_id' => 'games-paper'])->assertStatus(502)
        ->assertJsonPath('errors.0.detail', 'The selected catalog egg contains an invalid definition.');

    expect(Egg::query()->count())->toBe($before);
    Http::assertSentCount(2);
});

test('validates catalog ids with 422 before contacting the catalog', function (mixed $id): void {
    Http::fake([EggCatalogService::INDEX_URL => Http::response([])]);

    $this->postJson(route('api.admin.eggs.catalog.import'), ['catalog_id' => $id])->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'catalog_id');

    Http::assertNothingSent();
})->with(['missing' => null, 'array' => [['id']], 'too long' => str_repeat('x', 256)]);

test('rejects non administrators with 403 before contacting the catalog', function (string $method, string $route): void {
    $this->actingAsNonAdmin();
    Http::fake([EggCatalogService::INDEX_URL => Http::response([])]);

    $this->{$method}(route($route), ['catalog_id' => 'games-paper'])->assertForbidden();

    Http::assertNothingSent();
})->with([
    ['getJson', 'api.admin.eggs.catalog'],
    ['postJson', 'api.admin.eggs.catalog.refresh'],
    ['postJson', 'api.admin.eggs.catalog.import'],
]);

test('requires authentication with 401', function (): void {
    Auth::logout();
    Http::fake([EggCatalogService::INDEX_URL => Http::response([])]);

    $this->getJson(route('api.admin.eggs.catalog'))->assertUnauthorized();

    Http::assertNothingSent();
});
