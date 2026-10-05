<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Eggs\EggScriptControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Models\Egg;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a non-root-administrator. */
dataset('scriptEndpointsDataProvider', function () {
    return [['getJson', 'api.admin.eggs.script'], ['putJson', 'api.admin.eggs.script.update']];
});
test('get script', function () {
    $egg = Egg::factory()->create(['script_install' => 'echo "original"', 'script_entry' => 'bash', 'script_container' => 'alpine:3.4', 'script_is_privileged' => true]);
    $response = $this->getJson(route('api.admin.eggs.script', ['egg' => $egg->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'script' => ['privileged', 'install', 'entry', 'container', 'extends']]]);
    $response->assertJsonPath('object', 'egg');
    $response->assertJsonPath('attributes.id', $egg->id);
    $response->assertJsonPath('attributes.script.install', 'echo "original"');
    $response->assertJsonPath('attributes.script.entry', 'bash');
    $response->assertJsonPath('attributes.script.container', 'alpine:3.4');
    $response->assertJsonPath('attributes.script.privileged', true);
});
test('update script', function () {
    $egg = Egg::factory()->create(['script_install' => 'echo "original"', 'script_entry' => 'bash', 'script_container' => 'alpine:3.4']);
    $response = $this->putJson(route('api.admin.eggs.script.update', ['egg' => $egg->id]), ['script_install' => 'echo "updated"', 'script_is_privileged' => true, 'script_entry' => 'ash', 'script_container' => 'alpine:3.20']);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('object', 'egg');
    $response->assertJsonPath('attributes.id', $egg->id);
    $response->assertJsonPath('attributes.script.install', 'echo "updated"');
    $response->assertJsonPath('attributes.script.entry', 'ash');
    $response->assertJsonPath('attributes.script.container', 'alpine:3.20');
    $this->assertDatabaseHas('eggs', ['id' => $egg->id, 'script_install' => 'echo "updated"', 'script_entry' => 'ash', 'script_container' => 'alpine:3.20']);
    expect($egg->refresh()->script_install)->toBe('echo "updated"');
});
test('update script copies another egg script when the script is empty', function () {
    $source = Egg::factory()->create(['script_install' => 'echo "source"']);
    $egg = Egg::factory()->create(['script_install' => 'echo "original"']);
    $response = $this->putJson(route('api.admin.eggs.script.update', ['egg' => $egg->id]), ['script_install' => '', 'script_is_privileged' => true, 'script_entry' => 'bash', 'script_container' => 'alpine:3.20', 'copy_script_from' => $source->id]);
    $response->assertStatus(Response::HTTP_OK);
    $egg->refresh();
    expect($egg->script_install)->toBeNull();
    expect($egg->copy_script_install)->toBe('echo "source"');
});
test('update script copy from must be numeric', function () {
    $egg = Egg::factory()->create();
    $response = $this->putJson(route('api.admin.eggs.script.update', ['egg' => $egg->id]), ['copy_script_from' => 'not-a-number']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);
    $errors = collect($response->json('errors'));
    $error = $errors->firstWhere('meta.source_field', 'copy_script_from');
    expect($error)->not->toBeNull('Expected a validation error for the [copy_script_from] field.');
    expect($error['meta']['rule'])->toBe('numeric');
});
test('update script copy from invalid', function () {
    $egg = Egg::factory()->create();
    $response = $this->putJson(route('api.admin.eggs.script.update', ['egg' => $egg->id]), ['script_is_privileged' => true, 'script_entry' => 'bash', 'script_container' => 'alpine:3.4', 'copy_script_from' => 123456]);
    $response->assertStatus(Response::HTTP_BAD_REQUEST);
    $response->assertJsonPath('errors.0.code', 'InvalidCopyFromException');
});
test('non admin forbidden', function (string $method, string $routeName) {
    $this->actingAsNonAdmin();
    $egg = Egg::factory()->create();
    $response = $this->{$method}(route($routeName, ['egg' => $egg->id]));
    $this->assertAccessDeniedJson($response);
})->with('scriptEndpointsDataProvider');
