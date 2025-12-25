<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Files;

use Mockery\MockInterface;
use Pterodactyl\Models\Permission;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class CompressFilesTest extends ClientApiIntegrationTestCase
{
    public function testEndpointRequiresAuthorization(): void
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_CONTROL_CONSOLE]);

        $this->postJson($this->link($server, '/files/compress'))->assertUnauthorized();

        $this->actingAs($user)
            ->postJson($this->link($server, '/files/compress'))
            ->assertForbidden();
    }

    public function testEndpointTriggersWingsCall(): void
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_FILE_ARCHIVE]);

        $this->mock(DaemonFileRepository::class, function (MockInterface $mock) {
            $mock->expects('setServer->compressFiles')->with('/', ['test.txt'])->andReturn([
                'name' => 'test.tar.gz',
                'mime' => 'application/gzip',
            ]);
        });

        $this->actingAs($user)
            ->postJson($endpoint = $this->link($server, '/files/compress'), [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.meta', ['source_field' => 'files', 'rule' => 'required']);

        $this->postJson($endpoint, ['root' => '/', 'files' => ['test.txt']])
            ->assertOk()
            ->assertJsonPath('object', 'file_object')
            ->assertJsonPath('attributes.name', 'test.tar.gz')
            ->assertJsonPath('attributes.mimetype', 'application/gzip');
    }
}
