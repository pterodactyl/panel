<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\VersionControllerTest;

use Illuminate\Http\Response;
use Pterodactyl\Services\Helpers\SoftwareVersionService;
use Pterodactyl\Tests\Support\Fakes\FakeSoftwareVersionService;

uses(\Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase::class);
test('get version', function () {
    $this->app->instance(SoftwareVersionService::class, new FakeSoftwareVersionService(
        panel: '1.0.0',
        daemon: '1.0.0',
        discord: 'https://pterodactyl.io/discord',
        donations: 'https://github.com/sponsors/matthewpi',
        isLatestPanel: true,
    ));
    $response = $this->getJson(route('api.admin.version'));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonStructure(['current', 'latest', 'is_latest', 'daemon', 'discord', 'donations']);
    $response->assertJsonPath('current', config('app.version'));
    $response->assertJsonPath('latest', '1.0.0');
    $response->assertJsonPath('is_latest', true);
    $response->assertJsonPath('daemon', '1.0.0');
    $response->assertJsonPath('discord', 'https://pterodactyl.io/discord');
    $response->assertJsonPath('donations', 'https://github.com/sponsors/matthewpi');
});
test('non admin forbidden', function () {
    $this->actingAsNonAdmin();
    $response = $this->getJson(route('api.admin.version'));
    $this->assertAccessDeniedJson($response);
});
