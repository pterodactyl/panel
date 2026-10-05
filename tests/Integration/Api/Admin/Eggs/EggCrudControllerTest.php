<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Eggs\EggCrudControllerTest;

use Illuminate\Http\Response;
use Illuminate\Testing\Assert;
use Pterodactyl\Models\Egg;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 error when accessed by a non-root-administrator. */
dataset('eggEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.eggs'], ['getJson', 'api.admin.eggs.view'], ['postJson', 'api.admin.eggs.store'], ['putJson', 'api.admin.eggs.update'], ['delete', 'api.admin.eggs.delete']]);
test('get eggs', function (): void {
    $eggs = Egg::factory()->times(2)->create();
    $eggs = $eggs->map(fn (Egg $egg) => $egg->refresh());

    $response = $this->getJson(route('api.admin.eggs'));
    $response->assertStatus(Response::HTTP_OK);
    // Eggs are no longer scoped to a nest, so the listing covers the seeded eggs too.
    $response->assertJsonCount(Egg::query()->count(), 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'uuid', 'name', 'docker_images', 'force_outgoing_ip', 'config', 'script', 'startup', 'created_at', 'updated_at']], ['object', 'attributes' => ['id', 'uuid', 'name', 'docker_images', 'force_outgoing_ip', 'config', 'script', 'startup', 'created_at', 'updated_at']]]]);
    Assert::assertArraySubset(['object' => 'egg', 'attributes' => ['id' => $eggs[0]->id, 'uuid' => $eggs[0]->uuid, 'name' => $eggs[0]->name, 'author' => $eggs[0]->author, 'description' => $eggs[0]->description, 'docker_images' => $eggs[0]->docker_images, 'startup' => $eggs[0]->startup, 'force_outgoing_ip' => $eggs[0]->force_outgoing_ip]], collect($response->json('data'))->firstWhere('attributes.id', $eggs[0]->id), true);
    Assert::assertArraySubset(['object' => 'egg', 'attributes' => ['id' => $eggs[1]->id, 'uuid' => $eggs[1]->uuid, 'name' => $eggs[1]->name, 'author' => $eggs[1]->author, 'description' => $eggs[1]->description, 'docker_images' => $eggs[1]->docker_images, 'startup' => $eggs[1]->startup, 'force_outgoing_ip' => $eggs[1]->force_outgoing_ip]], collect($response->json('data'))->firstWhere('attributes.id', $eggs[1]->id), true);
});
test('get single egg', function (): void {
    $egg = Egg::factory()->create();
    $response = $this->getJson(route('api.admin.eggs.view', ['egg' => $egg->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'uuid', 'name', 'docker_images', 'force_outgoing_ip', 'config', 'script', 'startup', 'created_at', 'updated_at']]);
    $response->assertJson(['object' => 'egg', 'attributes' => ['id' => $egg->id, 'uuid' => $egg->uuid, 'name' => $egg->name, 'author' => $egg->author, 'description' => $egg->description, 'docker_images' => $egg->docker_images, 'startup' => $egg->startup, 'force_outgoing_ip' => $egg->force_outgoing_ip]]);
});
test('get missing egg', function (): void {
    $response = $this->getJson(route('api.admin.eggs.view', ['egg' => 'nil']));
    $this->assertNotFoundJson($response);
});
test('create egg', function (): void {
    $response = $this->postJson(route('api.admin.eggs.store'), validCreatePayload());
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'uuid', 'name', 'docker_images', 'force_outgoing_ip', 'config', 'script', 'startup', 'created_at', 'updated_at'], 'meta' => ['resource']]);
    $this->assertDatabaseHas('eggs', ['name' => 'Test Egg']);
    $egg = Egg::query()->where('name', 'Test Egg')->firstOrFail();
    expect($egg->docker_images)->toBe(['Java 17' => 'ghcr.io/pterodactyl/yolks:java_17', 'Java 11' => 'ghcr.io/pterodactyl/yolks:java_11']);
    $response->assertJson(['object' => 'egg', 'attributes' => ['id' => $egg->id, 'uuid' => $egg->uuid, 'name' => $egg->name, 'author' => $egg->author, 'description' => $egg->description, 'docker_images' => $egg->docker_images, 'startup' => $egg->startup, 'force_outgoing_ip' => $egg->force_outgoing_ip], 'meta' => ['resource' => route('api.admin.eggs.view', ['egg' => $egg->id])]]);
});
test('update egg', function (): void {
    $egg = Egg::factory()->create();
    $response = $this->putJson(route('api.admin.eggs.update', ['egg' => $egg->id]), array_merge(validCreatePayload(), ['name' => 'Updated Egg', 'startup' => 'java -jar updated.jar']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('object', 'egg');
    $response->assertJsonPath('attributes.name', 'Updated Egg');
    $response->assertJsonPath('attributes.startup', 'java -jar updated.jar');
    $this->assertDatabaseHas('eggs', ['id' => $egg->id, 'name' => 'Updated Egg']);
});
test('delete egg', function (): void {
    $egg = Egg::factory()->create();
    $this->assertDatabaseHas('eggs', ['id' => $egg->id]);
    $response = $this->delete(route('api.admin.eggs.delete', ['egg' => $egg->id]));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('eggs', ['id' => $egg->id]);
});
test('delete egg with servers', function (): void {
    $server = $this->createServerModel();
    $egg = Egg::query()->findOrFail($server->egg_id);
    $response = $this->delete(route('api.admin.eggs.delete', ['egg' => $egg->id]));
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    // Error handler rolls the transaction back on render, so only assert the rejected status.
    $response->assertJsonPath('errors.0.code', 'HasActiveServersException');
});
test('delete egg with children', function (): void {
    $parent = Egg::factory()->create();
    Egg::factory()->create(['config_from' => $parent->id]);
    $response = $this->delete(route('api.admin.eggs.delete', ['egg' => $parent->id]));
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    // Error handler rolls the transaction back on render, so only assert the rejected status.
    $response->assertJsonPath('errors.0.code', 'HasChildrenException');
});
test('unknown include is ignored', function (): void {
    $egg = Egg::factory()->create();
    $response = $this->getJson(route('api.admin.eggs.view', ['egg' => $egg->id, 'include' => 'variables,servers,nest']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('object', 'egg');
    $response->assertJsonPath('attributes.id', $egg->id);
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $this->actingAsNonAdmin();
    $egg = Egg::factory()->create();
    $response = $this->{$method}(route($routeName, ['egg' => $egg->id]));
    $this->assertAccessDeniedJson($response);
})->with('eggEndpointsDataProvider');
test('invalid payloads return validation errors', function (): void {
    $response = $this->postJson(route('api.admin.eggs.store'), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    $error = $errors->firstWhere('meta.source_field', 'name');
    expect($error)->not->toBeNull('Expected a validation error for the [name] field.');
    expect($error['meta']['rule'])->toBe('required');
    expect($error['detail'])->not->toBeEmpty();
});
/** Valid egg-creation payload; `docker_images` is a newline-delimited string the request normalizes into a `name => image` map. */
function validCreatePayload(): array
{
    return ['name' => 'Test Egg', 'description' => 'A freshly created egg.', 'docker_images' => "Java 17|ghcr.io/pterodactyl/yolks:java_17\nJava 11|ghcr.io/pterodactyl/yolks:java_11", 'startup' => 'java -jar server.jar', 'config_stop' => 'stop', 'config_startup' => '{"done": ["Done"]}', 'config_logs' => '{}', 'config_files' => '{}', 'features' => ['eula', 'fastdl']];
}

test('config from must reference an existing egg', function (): void {
    $response = $this->postJson(route('api.admin.eggs.store'), array_merge(validCreatePayload(), ['config_from' => 999999]));
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'config_from');
    $response->assertJsonPath('errors.0.meta.rule', 'exists');
});
test('config from zero means no parent egg', function (): void {
    $response = $this->postJson(route('api.admin.eggs.store'), array_merge(validCreatePayload(), ['config_from' => 0]));
    $response->assertStatus(Response::HTTP_CREATED);
    $this->assertDatabaseHas('eggs', ['name' => 'Test Egg', 'config_from' => null]);
});
test('config from references the parent egg', function (): void {
    $parent = Egg::factory()->create();
    $response = $this->postJson(route('api.admin.eggs.store'), array_merge(validCreatePayload(), ['config_from' => $parent->id]));
    $response->assertStatus(Response::HTTP_CREATED);
    $this->assertDatabaseHas('eggs', ['name' => 'Test Egg', 'config_from' => $parent->id]);
});
