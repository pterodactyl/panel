<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Application\Eggs\EggControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

uses(ApplicationApiIntegrationTestCase::class);
test('list all eggs', function (): void {
    $eggs = Egg::query()->get();
    $response = $this->getJson('/api/application/eggs');
    $response->assertOk();
    $response->assertJsonCount($eggs->count(), 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'uuid', 'author', 'description', 'docker_image', 'startup', 'created_at', 'updated_at', 'script' => ['privileged', 'install', 'entry', 'container', 'extends'], 'config' => ['files', 'startup', 'stop', 'logs', 'extends']]]], 'meta' => ['pagination']]);
    foreach ($eggs as $egg) {
        $attributes = collect($response->json('data'))->firstWhere('attributes.id', $egg->id)['attributes'];
        expect($attributes)->toMatchArray([
            'id' => $egg->id,
            'uuid' => $egg->uuid,
            'name' => $egg->name,
            'author' => $egg->author,
            'description' => $egg->description,
            'docker_image' => array_values($egg->docker_images)[0],
            'docker_images' => $egg->docker_images,
            'startup' => $egg->startup,
            'created_at' => $egg->created_at->toAtomString(),
            'updated_at' => $egg->updated_at->toAtomString(),
        ]);
        expect($attributes['script'])->toBe([
            'privileged' => $egg->script_is_privileged,
            'install' => $egg->script_install,
            'entry' => $egg->script_entry,
            'container' => $egg->script_container,
            'extends' => $egg->copy_script_from,
        ]);
        expect($attributes['config'])->toBe([
            'files' => json_decode($egg->config_files ?? 'null', true) ?: [],
            'startup' => json_decode($egg->config_startup ?? 'null', true),
            'stop' => $egg->config_stop,
            'logs' => json_decode($egg->config_logs ?? 'null', true),
            'file_denylist' => $egg->file_denylist,
            'extends' => $egg->config_from,
        ]);
    }
});
test('return single egg', function (): void {
    $egg = Egg::query()->findOrFail(1);
    $this->getJson('/api/application/eggs/'.$egg->id)->assertOk()->assertJson(['object' => 'egg', 'attributes' => ['id' => $egg->id, 'uuid' => $egg->uuid, 'name' => $egg->name, 'author' => $egg->author, 'description' => $egg->description, 'docker_images' => $egg->docker_images, 'startup' => $egg->startup]], true);
});
test('return single egg with relationships', function (): void {
    $egg = Egg::query()->findOrFail(1);
    $server = $this->createServerModel(['egg_id' => $egg->id]);
    $variable = EggVariable::factory()->create(['egg_id' => $egg->id]);
    $response = $this->getJson('/api/application/eggs/'.$egg->id.'?include=servers,variables')->assertOk();
    expect($response->json('attributes.relationships.servers.data.*.attributes.id'))->toContain($server->id);
    expect($response->json('attributes.relationships.variables.data.*.attributes.id'))->toContain($variable->id);
});
test('return single egg with its tags', function (): void {
    $egg = Egg::factory()->create();
    $tag = Tag::factory()->create();
    $egg->tags()->attach($tag);
    $response = $this->getJson('/api/application/eggs/'.$egg->id.'?include=tags')->assertOk();
    expect($response->json('attributes.relationships.tags.data.*.attributes.slug'))->toBe([$tag->slug]);
});
test('filter eggs by tag slug', function (): void {
    [$first, $second] = Tag::factory()->count(2)->create();
    $firstEgg = Egg::factory()->create();
    $firstEgg->tags()->attach($first);
    $secondEgg = Egg::factory()->create();
    $secondEgg->tags()->attach($second);
    expect($this->getJson('/api/application/eggs?filter[tag]='.$first->slug)->assertOk()->json('data.*.attributes.id'))->toBe([$firstEgg->id]);
    expect($this->getJson('/api/application/eggs?filter[tag]='.$first->slug.','.$second->slug.'&sort=id')->assertOk()->json('data.*.attributes.id'))->toBe([$firstEgg->id, $secondEgg->id]);
    expect($this->getJson('/api/application/eggs?filter[tag]=no-such-tag')->assertOk()->json('data'))->toBe([]);
});
test('retired nest include is ignored', function (): void {
    $egg = Egg::factory()->create();
    $this->getJson('/api/application/eggs/'.$egg->id.'?include=nest')->assertOk()->assertJsonMissingPath('attributes.nest')->assertJsonMissingPath('attributes.relationships.nest');
});
test('missing egg returns not found', function (): void {
    $this->assertNotFoundJson($this->getJson('/api/application/eggs/nil'));
});
test('read permission is required', function (): void {
    $this->createNewDefaultApiKey($this->getApiUser(), ['r_eggs' => 0]);
    $this->assertAccessDeniedJson($this->getJson('/api/application/eggs'));
});
test('nest endpoints do not exist', function (): void {
    $this->getJson('/api/application/nests')->assertStatus(Response::HTTP_NOT_FOUND);
});
