<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Mounts\MountControllerTest;

use Illuminate\Http\Response;
use Illuminate\Testing\Assert;
use Pterodactyl\Models\Mount;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a non-root-administrator. */
dataset('mountEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.mounts'], ['getJson', 'api.admin.mounts.view'], ['postJson', 'api.admin.mounts.store'], ['putJson', 'api.admin.mounts.update'], ['delete', 'api.admin.mounts.delete']]);
test('get mounts', function (): void {
    $mount = Mount::factory()->create();
    $response = $this->getJson(route('api.admin.mounts', ['per_page' => 60]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'uuid', 'name', 'description', 'source', 'target', 'read_only', 'user_mountable', 'eggs_count', 'nodes_count', 'servers_count']]], 'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    $response->assertJson(['object' => 'list', 'data' => [[]], 'meta' => ['pagination' => ['total' => 1, 'count' => 1, 'per_page' => 60, 'current_page' => 1, 'total_pages' => 1]]]);
    Assert::assertArraySubset(['object' => 'mount', 'attributes' => ['id' => $mount->id, 'uuid' => $mount->uuid, 'name' => $mount->name, 'description' => $mount->description, 'source' => $mount->source, 'target' => $mount->target, 'read_only' => $mount->read_only, 'user_mountable' => $mount->user_mountable, 'eggs_count' => 0, 'nodes_count' => 0, 'servers_count' => 0]], collect($response->json('data'))->firstWhere('attributes.id', $mount->id), true);
});
test('get mounts sorted by name', function (): void {
    $zebra = Mount::factory()->create(['name' => 'ZZ Sort Mount Zebra']);
    $alpha = Mount::factory()->create(['name' => 'ZZ Sort Mount Alpha']);
    $response = $this->getJson(route('api.admin.mounts', ['filter' => ['name' => 'ZZ Sort Mount'], 'sort' => 'name']));
    $response->assertOk()->assertJsonPath('data.0.attributes.id', $alpha->id);
    $this->assertNotSame($zebra->id, $alpha->id);
});
test('get single mount', function (): void {
    $mount = Mount::factory()->create();
    $response = $this->getJson(route('api.admin.mounts.view', ['mount' => $mount->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'uuid', 'name', 'description', 'source', 'target', 'read_only', 'user_mountable', 'eggs_count', 'nodes_count', 'servers_count']]);
    $response->assertJson(['object' => 'mount', 'attributes' => ['id' => $mount->id, 'uuid' => $mount->uuid, 'name' => $mount->name, 'description' => $mount->description, 'source' => $mount->source, 'target' => $mount->target, 'read_only' => $mount->read_only, 'user_mountable' => $mount->user_mountable, 'eggs_count' => 0, 'nodes_count' => 0, 'servers_count' => 0]]);
});
test('get missing mount', function (): void {
    $response = $this->getJson(route('api.admin.mounts.view', ['mount' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('create mount', function (): void {
    $response = $this->postJson(route('api.admin.mounts.store'), ['name' => 'Example Mount', 'description' => 'An example mount.', 'source' => '/mnt/example-source', 'target' => '/mnt/example-target', 'read_only' => true, 'user_mountable' => false]);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonCount(3);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'uuid', 'name', 'description', 'source', 'target', 'read_only', 'user_mountable', 'eggs_count', 'nodes_count', 'servers_count'], 'meta' => ['resource']]);
    $this->assertDatabaseHas('mounts', ['name' => 'Example Mount', 'source' => '/mnt/example-source']);
    $mount = Mount::query()->where('name', 'Example Mount')->first();
    $response->assertJson(['object' => 'mount', 'attributes' => ['id' => $mount->id, 'uuid' => $mount->uuid, 'name' => $mount->name, 'description' => $mount->description, 'source' => $mount->source, 'target' => $mount->target, 'read_only' => $mount->read_only, 'user_mountable' => $mount->user_mountable, 'eggs_count' => 0, 'nodes_count' => 0, 'servers_count' => 0], 'meta' => ['resource' => route('api.admin.mounts.view', ['mount' => $mount->id])]], true);
});
test('update mount', function (): void {
    $mount = Mount::factory()->create();
    $response = $this->putJson(route('api.admin.mounts.update', ['mount' => $mount->id]), ['name' => 'Updated Mount', 'description' => 'An updated mount.', 'source' => '/mnt/updated-source', 'target' => '/mnt/updated-target', 'read_only' => true, 'user_mountable' => true]);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'uuid', 'name', 'description', 'source', 'target', 'read_only', 'user_mountable', 'eggs_count', 'nodes_count', 'servers_count']]);
    $this->assertDatabaseHas('mounts', ['id' => $mount->id, 'name' => 'Updated Mount', 'source' => '/mnt/updated-source']);
    $mount = $mount->fresh();
    $response->assertJson(['object' => 'mount', 'attributes' => ['id' => $mount->id, 'uuid' => $mount->uuid, 'name' => $mount->name, 'description' => $mount->description, 'source' => $mount->source, 'target' => $mount->target, 'read_only' => $mount->read_only, 'user_mountable' => $mount->user_mountable, 'eggs_count' => 0, 'nodes_count' => 0, 'servers_count' => 0]]);
});
test('delete mount', function (): void {
    $mount = Mount::factory()->create();
    $this->assertDatabaseHas('mounts', ['id' => $mount->id]);
    $response = $this->delete(route('api.admin.mounts.delete', ['mount' => $mount->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('mounts', ['id' => $mount->id]);
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $this->actingAsNonAdmin();
    $mount = Mount::factory()->create();
    $response = $this->{$method}(route($routeName, ['mount' => $mount->id]));
    $this->assertAccessDeniedJson($response);
})->with('mountEndpointsDataProvider');
test('invalid payloads return validation errors', function (): void {
    $response = $this->postJson(route('api.admin.mounts.store'), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    foreach (['name', 'source', 'target'] as $field) {
        $error = $errors->firstWhere('meta.source_field', $field);
        expect($error)->not->toBeNull("Expected a validation error for the [{$field}] field.");
        expect($error['meta']['rule'])->toBe('required');
        expect($error['detail'])->not->toBeEmpty();
    }
});
