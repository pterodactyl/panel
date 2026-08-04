<?php

namespace Pterodactyl\Tests\Integration\Api\Application\Servers;

use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

class ServerManagementControllerTest extends ApplicationApiIntegrationTestCase
{
    /**
     * Test that a server can still be reinstalled via the admin
     * even if it is configured to skip its egg's install script.
     */
    public function testServerConfiguredToSkipScriptsCanStillBeReinstalled()
    {
        $server = $this->createServerModel(['skip_scripts' => true]);

        $service = \Mockery::mock(DaemonServerRepository::class);
        $this->app->instance(DaemonServerRepository::class, $service);

        $service->expects('setServer')
            ->with(\Mockery::on(fn ($value) => $value->uuid === $server->uuid))
            ->andReturnSelf()
            ->getMock()
            ->expects('reinstall')
            ->andReturnUndefined();

        $this->postJson('/api/application/servers/' . $server->id . '/reinstall')
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $server = $server->refresh();
        $this->assertSame(Server::STATUS_INSTALLING, $server->status);
        $this->assertTrue($server->skip_scripts);
    }
}
