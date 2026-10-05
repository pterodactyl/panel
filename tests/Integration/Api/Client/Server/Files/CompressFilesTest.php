<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\Files\CompressFilesTest;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;
use Pterodactyl\Tests\Support\Fakes\FakeDaemonFile;
use Pterodactyl\Transformers\Api\Client\ActivityLogTransformer;

uses(ClientApiIntegrationTestCase::class);
test('endpoint requires authorization', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ControlConsole->value]);
    $this->postJson($this->link($server, '/files/compress'))->assertUnauthorized();
    $this->actingAs($user)->postJson($this->link($server, '/files/compress'))->assertForbidden();
});
test('endpoint triggers wings call', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::FileArchive->value]);
    $fake = new FakeDaemonFile;
    $fake->compressed = ['name' => 'test.tar.gz', 'mime' => 'application/gzip'];
    $this->actingAs($user)->postJson($endpoint = $this->link($server, '/files/compress'), [])->assertUnprocessable()->assertJsonPath('errors.0.meta', ['source_field' => 'files', 'rule' => 'required']);
    $this->postJson($endpoint, ['root' => '/', 'files' => ['test.txt']])->assertOk()->assertJsonPath('object', 'file_object')->assertJsonPath('attributes.name', 'test.tar.gz')->assertJsonPath('attributes.mimetype', 'application/gzip');
    $fake->assertCompressedFiles('/', ['test.txt']);
});
test('compress description only interpolates properties the event records', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::FileArchive->value]);
    $fake = new FakeDaemonFile;
    $fake->compressed = ['name' => 'test.tar.gz', 'mime' => 'application/gzip'];
    $this->actingAs($user)->postJson($this->link($server, '/files/compress'), ['root' => '/logs', 'files' => ['latest.log']])->assertOk();

    $log = ActivityLog::query()->where('event', 'server:file.compress')->latest('id')->firstOrFail();
    $properties = (array) app(ActivityLogTransformer::class)->transform($log)['properties'];

    // A single file resolves the "_one" plural, whose placeholders must all be present
    // in the transformed properties or the front-end renders a raw "{{name}}" token.
    expect($properties['count'])->toBe(1);
    preg_match_all('/:([\\w.-]+\\w)/', trans('activity.server.file.compress_one'), $matches);
    foreach ($matches[1] as $placeholder) {
        expect($properties)->toHaveKey(explode('.', $placeholder)[0]);
    }
});
