<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Location\LocationControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

uses(ApplicationApiIntegrationTestCase::class);
test('get locations', function (): void {
    $locations = Location::factory()->times(2)->create();
    $response = $this->getJson('/api/application/locations?per_page=60');
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at']], ['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at']]], 'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    $response->assertJson(['object' => 'list', 'data' => [[], []], 'meta' => ['pagination' => ['total' => 2, 'count' => 2, 'per_page' => 60, 'current_page' => 1, 'total_pages' => 1]]])->assertJsonFragment(['object' => 'location', 'attributes' => ['id' => $locations[0]->id, 'short' => $locations[0]->short, 'long' => $locations[0]->long, 'relationships' => [], 'created_at' => $this->formatTimestamp($locations[0]->created_at), 'updated_at' => $this->formatTimestamp($locations[0]->updated_at)]])->assertJsonFragment(['object' => 'location', 'attributes' => ['id' => $locations[1]->id, 'short' => $locations[1]->short, 'long' => $locations[1]->long, 'relationships' => [], 'created_at' => $this->formatTimestamp($locations[1]->created_at), 'updated_at' => $this->formatTimestamp($locations[1]->updated_at)]]);
});
test('get single location', function (): void {
    $location = Location::factory()->create();
    $response = $this->getJson('/api/application/locations/'.$location->id);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at']]);
    $response->assertJson(['object' => 'location', 'attributes' => ['id' => $location->id, 'short' => $location->short, 'long' => $location->long, 'created_at' => $this->formatTimestamp($location->created_at), 'updated_at' => $this->formatTimestamp($location->updated_at)]], true);
});
test('create location', function (): void {
    $response = $this->postJson('/api/application/locations', ['short' => 'inhouse', 'long' => 'This is my inhouse location']);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonCount(3);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at'], 'meta' => ['resource']]);
    $this->assertDatabaseHas('locations', ['short' => 'inhouse', 'long' => 'This is my inhouse location']);
    $location = Location::query()->where('short', 'inhouse')->first();
    $response->assertJson(['object' => 'location', 'attributes' => ['id' => $location->id, 'short' => $location->short, 'long' => $location->long], 'meta' => ['resource' => route('api.application.locations.view', $location->id)]], true);
});
test('update location', function (): void {
    $location = Location::factory()->create();
    $response = $this->patchJson('/api/application/locations/'.$location->id, ['short' => 'new inhouse', 'long' => 'This is my new inhouse location']);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at']]);
    $this->assertDatabaseHas('locations', ['short' => 'new inhouse', 'long' => 'This is my new inhouse location']);
    $location = $location->fresh();
    $response->assertJson(['object' => 'location', 'attributes' => ['id' => $location->id, 'short' => $location->short, 'long' => $location->long]]);
});
test('delete location', function (): void {
    $location = Location::factory()->create();
    $this->assertDatabaseHas('locations', ['id' => $location->id]);
    $response = $this->delete('/api/application/locations/'.$location->id);
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('locations', ['id' => $location->id]);
});
test('relationships can be loaded', function (): void {
    $location = Location::factory()->create();
    $server = $this->createServerModel(['user_id' => $this->getApiUser()->id, 'location_id' => $location->id]);
    $response = $this->getJson('/api/application/locations/'.$location->id.'?include=servers,nodes');
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2)->assertJsonCount(2, 'attributes.relationships');
    $response->assertJsonStructure(['attributes' => ['relationships' => ['nodes' => ['object', 'data' => [['attributes' => ['id']]]], 'servers' => ['object', 'data' => [['attributes' => ['id']]]]]]]);
    // Just assert that we see the expected relationship IDs in the response.
    $response->assertJson(['attributes' => ['relationships' => ['nodes' => ['object' => 'list', 'data' => [['object' => 'node', 'attributes' => ['id' => $server->node_id, 'location_id' => $location->id]]]], 'servers' => ['object' => 'list', 'data' => [['object' => 'server', 'attributes' => ['id' => $server->id, 'uuid' => $server->uuid, 'identifier' => $server->uuidShort, 'name' => $server->name, 'user' => $server->owner_id, 'node' => $server->node_id]]]]]]]);
});
test('key without permission cannot load relationship', function (): void {
    $this->createNewDefaultApiKey($this->getApiUser(), ['r_nodes' => 0]);
    $location = Location::factory()->create();
    Node::factory()->create(['location_id' => $location->id]);
    $response = $this->getJson('/api/application/locations/'.$location->id.'?include=nodes');
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2)->assertJsonCount(1, 'attributes.relationships');
    $response->assertJsonStructure(['attributes' => ['relationships' => ['nodes' => ['object', 'attributes']]]]);
    // Just assert that we see the expected relationship IDs in the response.
    $response->assertJson(['attributes' => ['relationships' => ['nodes' => ['object' => 'null_resource', 'attributes' => null]]]]);
});
test('get missing location', function (): void {
    $response = $this->getJson('/api/application/locations/nil');
    $this->assertNotFoundJson($response);
});
test('error returned if no permission', function (): void {
    $location = Location::factory()->create();
    $this->createNewDefaultApiKey($this->getApiUser(), ['r_locations' => 0]);
    $response = $this->getJson('/api/application/locations/'.$location->id);
    $this->assertAccessDeniedJson($response);
});
