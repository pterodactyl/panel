<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Eggs\EggShareControllerTest;

use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Pterodactyl\Contracts\Eggs\ExportsEggs;
use Pterodactyl\Models\Egg;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 error when accessed by a user that is not a root administrator. */
dataset('shareEndpointsDataProvider', function () {
    return [['getJson', 'api.admin.eggs.export'], ['postJson', 'api.admin.eggs.import'], ['postJson', 'api.admin.eggs.update-import']];
});
test('export', function () {
    $egg = bungeecordEgg();
    $response = $this->get(route('api.admin.eggs.export', ['egg' => $egg->id]));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertHeader('Content-Type', 'application/json');
    $decoded = json_decode($response->getContent(), true);
    expect($decoded)->toBeArray();
    expect($decoded['meta']['version'] ?? null)->toBe(Egg::EXPORT_VERSION);
    expect($decoded['name'] ?? null)->toBe($egg->name);
});
test('import', function () {
    $source = bungeecordEgg();
    $json = app(ExportsEggs::class)->export($source);
    $before = Egg::query()->count();
    $response = $this->post(route('api.admin.eggs.import'), ['import_file' => uploadedJsonFile($json)]);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'uuid', 'name', 'docker_images', 'config', 'script', 'startup', 'created_at', 'updated_at'], 'meta' => ['resource']]);
    $response->assertJsonPath('object', 'egg');
    $response->assertJsonPath('attributes.name', $source->name);
    expect(Egg::query()->count())->toBe($before + 1);
    $this->assertDatabaseHas('eggs', ['name' => $source->name]);
});
test('update import', function () {
    $source = bungeecordEgg();
    $target = Egg::factory()->create();
    $json = app(ExportsEggs::class)->export($source);
    $response = $this->post(route('api.admin.eggs.update-import', ['egg' => $target->id]), ['import_file' => uploadedJsonFile($json)]);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('object', 'egg');
    $response->assertJsonPath('attributes.id', $target->id);
    $response->assertJsonPath('attributes.name', $source->name);
    $this->assertDatabaseHas('eggs', ['id' => $target->id, 'name' => $source->name]);
});
test('import validation missing file', function () {
    $response = $this->postJson(route('api.admin.eggs.import'), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'import_file');
    $response->assertJsonPath('errors.0.meta.rule', 'required');
});
test('update import validation missing file', function () {
    $egg = Egg::factory()->create();
    $response = $this->postJson(route('api.admin.eggs.update-import', ['egg' => $egg->id]), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'import_file');
    $response->assertJsonPath('errors.0.meta.rule', 'required');
});
test('non admin forbidden', function (string $method, string $routeName) {
    $this->actingAsNonAdmin();
    $egg = Egg::factory()->create();
    $response = $this->{$method}(route($routeName, ['egg' => $egg->id]));
    $this->assertAccessDeniedJson($response);
})->with('shareEndpointsDataProvider');
/** Return the seeded Bungeecord egg, the canonical egg every other integration test relies upon. */
function bungeecordEgg(): Egg
{
    /** @var Egg $egg */
    $egg = Egg::query()->where('author', 'support@pterodactyl.io')->where('name', 'Bungeecord')->firstOrFail();

    return $egg;
}
/** Build an UploadedFile from a JSON string, flagged as a test upload so the parser's isFile()/getError() checks pass. */
function uploadedJsonFile(string $json): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'egg').'.json';
    file_put_contents($path, $json);

    return new UploadedFile($path, 'egg.json', 'application/json', null, true);
}
