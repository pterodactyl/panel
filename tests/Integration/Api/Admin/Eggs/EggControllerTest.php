<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Eggs\EggControllerTest;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Tag;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
test('nest endpoints do not exist', function () {
    $this->getJson('/api/admin/nests')->assertNotFound();
    // The blade admin was removed entirely; stray /admin URLs now fall through to
    // the React SPA shell, which handles them client side.
    $this->get('/admin/nests')->assertOk()->assertViewIs('templates.base.core');
});
test('eggs can be filtered by name', function () {
    /** @var Egg $egg */
    $egg = Egg::factory()->create(['name' => 'Filterable Egg']);
    Egg::factory()->create(['name' => 'Other Egg']);
    $this->getJson(route('api.admin.eggs', ['filter' => ['name' => 'Filterable Egg']]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attributes.id', $egg->id);
});
test('egg tags can be included', function () {
    /** @var Egg $egg */
    $egg = Egg::factory()->create();
    /** @var Tag $tag */
    $tag = Tag::factory()->create(['name' => 'Minecraft', 'slug' => 'minecraft']);
    $egg->tags()->attach($tag);

    $this->getJson(route('api.admin.eggs.view', ['egg' => $egg->id, 'include' => 'tags']))
        ->assertOk()
        ->assertJsonPath('attributes.relationships.tags.data.0.attributes.id', $tag->id)
        ->assertJsonPath('attributes.relationships.tags.data.0.attributes.slug', 'minecraft');
});
test('egg can be created', function () {
    $response = $this->postJson(route('api.admin.eggs.store'), ['name' => 'Standalone Egg', 'description' => 'Grouped by tags.', 'docker_images' => 'Java 21|ghcr.io/pterodactyl/yolks:java_21', 'startup' => 'java -jar server.jar', 'config_stop' => 'stop', 'config_startup' => '{"done":["Done"]}', 'config_logs' => '{}', 'config_files' => '{}']);
    $response->assertCreated()->assertJsonPath('attributes.name', 'Standalone Egg');
    $this->assertDatabaseHas('eggs', ['name' => 'Standalone Egg']);
});
