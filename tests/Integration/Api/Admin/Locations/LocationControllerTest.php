<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Locations\LocationControllerTest;

use Illuminate\Http\Response;
use Illuminate\Testing\Assert;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a non-root-administrator. */
dataset('locationEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.locations'], ['getJson', 'api.admin.locations.view'], ['getJson', 'api.admin.locations.eligible-nodes'], ['postJson', 'api.admin.locations.store'], ['putJson', 'api.admin.locations.update'], ['delete', 'api.admin.locations.delete']]);
dataset('locationListSortsDataProvider', fn (): array => [['short'], ['-short'], ['created_at'], ['-created_at']]);
test('get locations', function (): void {
    $locations = Location::factory()->times(2)->create();
    $response = $this->getJson(route('api.admin.locations', ['per_page' => 60]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at']], ['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at']]], 'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    $response->assertJson(['object' => 'list', 'data' => [[], []], 'meta' => ['pagination' => ['total' => 2, 'count' => 2, 'per_page' => 60, 'current_page' => 1, 'total_pages' => 1]]]);
    Assert::assertArraySubset(['object' => 'location', 'attributes' => ['id' => $locations[0]->id, 'short' => $locations[0]->short, 'long' => $locations[0]->long]], collect($response->json('data'))->firstWhere('attributes.id', $locations[0]->id), true);
    Assert::assertArraySubset(['object' => 'location', 'attributes' => ['id' => $locations[1]->id, 'short' => $locations[1]->short, 'long' => $locations[1]->long]], collect($response->json('data'))->firstWhere('attributes.id', $locations[1]->id), true);
});
test('get locations accepts data table sorts', function (string $sort): void {
    Location::factory()->create(['short' => 'datatable-a', 'long' => 'Data table sort location']);
    Location::factory()->create(['short' => 'datatable-z', 'long' => 'Data table sort location']);
    $response = $this->getJson(route('api.admin.locations', ['filter' => ['long' => 'Data table sort location'], 'sort' => $sort, 'per_page' => 100]));
    $response->assertOk();
    $response->assertJsonCount(2, 'data');
})->with('locationListSortsDataProvider');
test('get single location', function (): void {
    $location = Location::factory()->create();
    $response = $this->getJson(route('api.admin.locations.view', ['location' => $location->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at']]);
    $response->assertJson(['object' => 'location', 'attributes' => ['id' => $location->id, 'short' => $location->short, 'long' => $location->long]]);
});
test('get missing location', function (): void {
    $response = $this->getJson(route('api.admin.locations.view', ['location' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('create location', function (): void {
    $response = $this->postJson(route('api.admin.locations.store'), ['short' => 'inerror', 'long' => 'This is a test long description.']);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonCount(3);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at'], 'meta' => ['resource']]);
    $this->assertDatabaseHas('locations', ['short' => 'inerror', 'long' => 'This is a test long description.']);
    $location = Location::query()->where('short', 'inerror')->first();
    $response->assertJson(['object' => 'location', 'attributes' => ['id' => $location->id, 'short' => $location->short, 'long' => $location->long], 'meta' => ['resource' => route('api.admin.locations.view', ['location' => $location->id])]], true);
});
test('update location', function (): void {
    $location = Location::factory()->create();
    $response = $this->putJson(route('api.admin.locations.update', ['location' => $location->id]), ['short' => 'newshort', 'long' => 'This is an updated description.']);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at']]);
    $this->assertDatabaseHas('locations', ['short' => 'newshort', 'long' => 'This is an updated description.']);
    $location = $location->fresh();
    $response->assertJson(['object' => 'location', 'attributes' => ['id' => $location->id, 'short' => $location->short, 'long' => $location->long]]);
});
test('delete location', function (): void {
    $location = Location::factory()->create();
    $this->assertDatabaseHas('locations', ['id' => $location->id]);
    $response = $this->delete(route('api.admin.locations.delete', ['location' => $location->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('locations', ['id' => $location->id]);
});
test('eligible nodes', function (): void {
    $location = Location::factory()->create();
    $nodes = Node::factory()->times(2)->create(['location_id' => $location->id]);
    // A node in a different location must not leak into the response.
    Node::factory()->create(['location_id' => Location::factory()->create()->id]);
    $response = $this->getJson(route('api.admin.locations.eligible-nodes', ['location' => $location->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonFragment(['id' => $nodes[0]->id]);
    $response->assertJsonFragment(['id' => $nodes[1]->id]);
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $this->actingAsNonAdmin();
    $location = Location::factory()->create();
    $response = $this->{$method}(route($routeName, ['location' => $location->id]));
    $this->assertAccessDeniedJson($response);
})->with('locationEndpointsDataProvider');
test('invalid payloads return validation errors', function (): void {
    $response = $this->postJson(route('api.admin.locations.store'), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    $error = $errors->firstWhere('meta.source_field', 'short');
    expect($error)->not->toBeNull('Expected a validation error for the [short] field.');
    expect($error['meta']['rule'])->toBe('required');
    expect($error['detail'])->not->toBeEmpty();
});
test('duplicate short code is rejected', function (): void {
    Location::factory()->create(['short' => 'taken']);
    $response = $this->postJson(route('api.admin.locations.store'), ['short' => 'taken', 'long' => 'Duplicate short code.']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'short');
    $response->assertJsonPath('errors.0.meta.rule', 'unique');
});
test('update keeps its own short code without a uniqueness clash', function (): void {
    $location = Location::factory()->create(['short' => 'keep-me']);
    $response = $this->putJson(route('api.admin.locations.update', ['location' => $location->id]), ['short' => 'keep-me', 'long' => 'Renamed description.']);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('attributes.long', 'Renamed description.');
});
test('delete location with nodes', function (): void {
    $location = Location::factory()->create();
    Node::factory()->create(['location_id' => $location->id]);
    $response = $this->delete(route('api.admin.locations.delete', ['location' => $location->id]));
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'HasActiveNodesException');
    $this->assertDatabaseHas('locations', ['id' => $location->id]);
});
