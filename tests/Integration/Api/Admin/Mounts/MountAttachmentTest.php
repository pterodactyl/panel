<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Mounts\MountAttachmentTest;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Mount;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a non-root-admin user. */
dataset('attachmentEndpointsDataProvider', fn (): array => [['postJson', 'api.admin.mounts.eggs'], ['deleteJson', 'api.admin.mounts.eggs.delete'], ['postJson', 'api.admin.mounts.nodes'], ['deleteJson', 'api.admin.mounts.nodes.delete']]);
test('eggs can be attached and detached', function (): void {
    $mount = Mount::factory()->create();
    $egg = createEgg();
    $response = $this->postJson(route('api.admin.mounts.eggs', ['mount' => $mount->id]), ['eggs' => [$egg->id]]);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJson(['object' => 'mount', 'attributes' => ['id' => $mount->id]]);
    $this->assertDatabaseHas('egg_mount', ['mount_id' => $mount->id, 'egg_id' => $egg->id]);
    // Attaching the same egg again must remain idempotent (no duplicate pivot rows).
    $repeat = $this->postJson(route('api.admin.mounts.eggs', ['mount' => $mount->id]), ['eggs' => [$egg->id]]);
    $repeat->assertStatus(Response::HTTP_OK);
    $this->assertDatabaseCount('egg_mount', 1);
    $detach = $this->delete(route('api.admin.mounts.eggs.delete', ['mount' => $mount->id, 'egg' => $egg->id]));
    $detach->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('egg_mount', ['mount_id' => $mount->id, 'egg_id' => $egg->id]);
});
test('nodes can be attached and detached', function (): void {
    $mount = Mount::factory()->create();
    $node = createNode();
    $response = $this->postJson(route('api.admin.mounts.nodes', ['mount' => $mount->id]), ['nodes' => [$node->id]]);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJson(['object' => 'mount', 'attributes' => ['id' => $mount->id]]);
    $this->assertDatabaseHas('mount_node', ['mount_id' => $mount->id, 'node_id' => $node->id]);
    // Attaching the same node again must remain idempotent (no duplicate pivot rows).
    $repeat = $this->postJson(route('api.admin.mounts.nodes', ['mount' => $mount->id]), ['nodes' => [$node->id]]);
    $repeat->assertStatus(Response::HTTP_OK);
    $this->assertDatabaseCount('mount_node', 1);
    $detach = $this->delete(route('api.admin.mounts.nodes.delete', ['mount' => $mount->id, 'node' => $node->id]));
    $detach->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('mount_node', ['mount_id' => $mount->id, 'node_id' => $node->id]);
});
test('includes can be loaded', function (): void {
    $mount = Mount::factory()->create();
    $egg = createEgg();
    $node = createNode();
    $mount->eggs()->attach($egg->id);
    $mount->nodes()->attach($node->id);
    $response = $this->getJson(route('api.admin.mounts.view', ['mount' => $mount->id, 'include' => 'eggs,nodes,servers']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['object', 'attributes' => ['relationships' => ['eggs' => ['object', 'data'], 'nodes' => ['object', 'data'], 'servers' => ['object', 'data']]]]);
    $response->assertJsonPath('attributes.relationships.eggs.data.0.attributes.id', $egg->id);
    $response->assertJsonPath('attributes.relationships.nodes.data.0.attributes.id', $node->id);
    // The index endpoint must also accept the includes without raising a 500.
    $index = $this->getJson(route('api.admin.mounts', ['include' => 'eggs,nodes,servers']));
    $index->assertStatus(Response::HTTP_OK);
    $index->assertJsonStructure(['data' => [['attributes' => ['relationships' => ['eggs' => ['object', 'data'], 'nodes' => ['object', 'data'], 'servers' => ['object', 'data']]]]]]);
});
test('attaching missing egg returns validation error', function (): void {
    $mount = Mount::factory()->create();
    $response = $this->postJson(route('api.admin.mounts.eggs', ['mount' => $mount->id]), ['eggs' => [9999]]);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.code', 'ValidationException');
    $response->assertJsonPath('errors.0.meta.source_field', 'eggs.0');
    $response->assertJsonPath('errors.0.meta.rule', 'exists');
});
test('attaching missing node returns validation error', function (): void {
    $mount = Mount::factory()->create();
    $response = $this->postJson(route('api.admin.mounts.nodes', ['mount' => $mount->id]), ['nodes' => [9999]]);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.code', 'ValidationException');
    $response->assertJsonPath('errors.0.meta.source_field', 'nodes.0');
    $response->assertJsonPath('errors.0.meta.rule', 'exists');
});
test('detaching missing egg returns not found', function (): void {
    $mount = Mount::factory()->create();
    $response = $this->delete(route('api.admin.mounts.eggs.delete', ['mount' => $mount->id, 'egg' => 9999]));
    $this->assertNotFoundJson($response);
});
test('detaching missing node returns not found', function (): void {
    $mount = Mount::factory()->create();
    $response = $this->delete(route('api.admin.mounts.nodes.delete', ['mount' => $mount->id, 'node' => 9999]));
    $this->assertNotFoundJson($response);
});
test('detaching unattached egg returns not found', function (): void {
    $mount = Mount::factory()->create();
    $attached = createEgg();
    $unattached = createEgg();
    $mount->eggs()->attach($attached->id);
    $response = $this->delete(route('api.admin.mounts.eggs.delete', ['mount' => $mount->id, 'egg' => $unattached->id]));
    $this->assertNotFoundJson($response);
    // Error handler rolls the transaction back on render, so only assert the rejected status.
});
test('detaching unattached node returns not found', function (): void {
    $mount = Mount::factory()->create();
    $attached = createNode();
    $unattached = createNode();
    $mount->nodes()->attach($attached->id);
    $response = $this->delete(route('api.admin.mounts.nodes.delete', ['mount' => $mount->id, 'node' => $unattached->id]));
    $this->assertNotFoundJson($response);
    // Error handler rolls the transaction back on render, so only assert the rejected status.
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $this->actingAsNonAdmin();
    $mount = Mount::factory()->create();
    $egg = createEgg();
    $node = createNode();
    $mount->eggs()->attach($egg->id);
    $mount->nodes()->attach($node->id);
    $url = route($routeName, ['mount' => $mount->id, 'egg' => $egg->id, 'node' => $node->id]);
    $response = $this->{$method}($url);
    $this->assertAccessDeniedJson($response);
})->with('attachmentEndpointsDataProvider');
/** Create a standalone egg so tests don't depend on seeded egg state. */
function createEgg(): Egg
{
    return Egg::factory()->create();
}

/** Create a node backed by a freshly created location. */
function createNode(): Node
{
    $location = Location::factory()->create();

    return Node::factory()->create(['location_id' => $location->id]);
}

test('mount attachment validates all ids with one lookup', function (string $resource): void {
    $mount = Mount::factory()->create();
    $models = $resource === 'nodes'
        ? Node::factory()->withLocation()->count(10)->create()
        : Egg::factory()->count(10)->create();
    $counts = [];
    foreach ([1, 10] as $limit) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $this->postJson(route('api.admin.mounts.'.$resource, ['mount' => $mount->id]), [$resource => $models->take($limit)->modelKeys()])->assertOk();
            $reads = array_filter(DB::getQueryLog(), fn (array $query): bool => str_starts_with(mb_strtolower($query['query']), 'select'));
            $counts[] = count($reads);
        } finally {
            DB::disableQueryLog();
        }
    }

    expect($counts[1])->toBe($counts[0]);
    expect($mount->{$resource}()->count())->toBe(10);
})->with(['nodes', 'eggs']);

test('mount attachment accepts numeric string identifiers', function (string $resource): void {
    $mount = Mount::factory()->create();
    $model = $resource === 'nodes' ? createNode() : createEgg();

    $this->postJson(route('api.admin.mounts.'.$resource, ['mount' => $mount->id]), [$resource => [(string) $model->id]])
        ->assertOk();

    expect($mount->{$resource}()->whereKey($model->id)->exists())->toBeTrue();
})->with(['nodes', 'eggs']);

test('mount attachment rejects associative identifiers with 422', function (string $resource): void {
    $mount = Mount::factory()->create();
    $model = $resource === 'nodes' ? createNode() : createEgg();

    $this->postJson(route('api.admin.mounts.'.$resource, ['mount' => $mount->id]), [$resource => ['selected' => $model->id]])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', $resource);

    expect($mount->{$resource}()->exists())->toBeFalse();
})->with(['nodes', 'eggs']);

test('mount attachment rejects malformed identifier values with 422', function (string $resource, bool|float|string|null $value): void {
    $mount = Mount::factory()->create();

    $this->postJson(route('api.admin.mounts.'.$resource, ['mount' => $mount->id]), [$resource => [$value]], options: JSON_PRESERVE_ZERO_FRACTION)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', $resource.'.0');

    expect($mount->{$resource}()->exists())->toBeFalse();
})->with(['nodes', 'eggs'])->with(['boolean' => true, 'whole float' => 1.0, 'empty string' => '', 'null' => [null]]);

test('mount collection batches requested includes independently of page size', function (): void {
    $mounts = Mount::factory()->count(10)->create();
    foreach ($mounts as $mount) {
        $mount->eggs()->attach(createEgg());
        $mount->nodes()->attach(createNode());
    }

    $counts = [];
    foreach ([1, 10] as $limit) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $this->getJson(route('api.admin.mounts', ['include' => 'eggs,nodes,servers', 'per_page' => $limit]))
                ->assertOk()
                ->assertJsonCount($limit, 'data');
            $counts[] = count(array_filter(DB::getQueryLog(), fn (array $query): bool => str_starts_with(mb_strtolower($query['query']), 'select')));
        } finally {
            DB::disableQueryLog();
        }
    }

    expect($counts[1])->toBe($counts[0]);
});
