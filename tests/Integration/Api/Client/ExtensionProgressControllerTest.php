<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\ExtensionProgressControllerTest;

use Illuminate\Support\Facades\File;
use Pterodactyl\Contracts\Extensions\InstallsExtensions;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Extensions\ExtensionJobProgress;
use Pterodactyl\Services\Extensions\ExtensionRepository;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().'/ptero-progress-'.uniqid();
    File::ensureDirectoryExists($this->directory.'/source');
    File::put($this->directory.'/source/extension.json', json_encode(['id' => 'probe', 'name' => 'Probe', 'version' => '1.0.0'], JSON_THROW_ON_ERROR));
    config(['extensions.enabled' => true, 'extensions.directory' => $this->directory.'/installed']);
    $this->app->make(ExtensionRepository::class)->flushDiscovery();
    $this->app->make(InstallsExtensions::class)->install($this->directory.'/source', enable: true);
    $this->progress = $this->app->make(ExtensionJobProgress::class);
});
afterEach(function (): void {
    \Pterodactyl\Models\Extension::query()->where('identifier', 'probe')->delete();
    File::deleteDirectory($this->directory);
});

test('user progress requires authentication and the matching owner namespace and scope', function (): void {
    $user = User::factory()->create();
    $job = $this->progress->begin('probe', $user);
    $url = route('api:client.extension-progress', ['extension' => 'probe', 'job' => $job->id]);
    $this->getJson($url)->assertUnauthorized();
    $this->actingAs($user)->getJson($url)->assertOk()->assertJsonPath('data.sequence', 1)->assertJsonMissingPath('data.userUuid');
    $updated = $this->progress->update('probe', $job->id, 75, 'Almost done');
    $this->getJson($url)->assertOk()->assertJsonPath('data.percent', 75)->assertJsonPath('data.sequence', $updated->sequence);
    $this->actingAs(User::factory()->create())->getJson($url)->assertNotFound();
    $this->actingAs($user)->getJson(route('api:client.extension-progress', ['extension' => 'other', 'job' => $job->id]))->assertNotFound();
    $this->getJson(route('api:client.extension-progress', ['extension' => 'probe', 'job' => 'invalid']))->assertNotFound();
});

test('server progress enforces server access operation permissions disabled extensions and expiry', function (): void {
    [$user, $server] = $this->generateTestAccount(['ext.probe.view']);
    $job = $this->progress->begin('probe', $user, $server, 'ext.probe.view');
    $url = route('api:client:server.extension-progress', ['server' => $server->uuid, 'extension' => 'probe', 'job' => $job->id]);
    $this->actingAs($user)->getJson($url)->assertOk()->assertJsonPath('data.id', $job->id);
    $this->getJson(route('api:client.extension-progress', ['extension' => 'probe', 'job' => $job->id]))->assertNotFound();
    $otherServer = $this->createServerModel(['user_id' => $user->id]);
    $this->getJson(route('api:client:server.extension-progress', ['server' => $otherServer->uuid, 'extension' => 'probe', 'job' => $job->id]))->assertNotFound();
    $server->subusers()->where('user_id', $user->id)->update(['permissions' => ['backup.read']]);
    $this->getJson($url)->assertNotFound();
    $this->actingAs($server->user)->getJson($url)->assertOk();
    config(['extensions.enabled' => false]);
    $this->getJson($url)->assertNotFound();
    config(['extensions.enabled' => true]);
    $this->travel(61)->minutes();
    $this->getJson($url)->assertNotFound();
});
