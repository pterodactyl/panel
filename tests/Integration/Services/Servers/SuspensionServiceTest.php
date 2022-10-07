<?php

namespace Pterodactyl\Tests\Integration\Services\Servers;

use Mockery;
use Mockery\MockInterface;
use InvalidArgumentException;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\SuspensionService;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Repositories\Wings\DaemonServerRepository;

class SuspensionServiceTest extends IntegrationTestCase
{
    private MockInterface $repository;

    /**
     * Setup test instance.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(DaemonServerRepository::class);
        $this->app->instance(DaemonServerRepository::class, $this->repository);
    }

    public function testServerIsSuspendedAndUnsuspended()
    {
        $server = $this->createServerModel();

        $this->repository->expects('setServer->sync')->twice()->andReturnSelf();

        $this->getService()->toggle($server);

        $this->assertTrue($server->refresh()->isSuspended());

        $this->getService()->toggle($server, SuspensionService::ACTION_UNSUSPEND);

        $this->assertFalse($server->refresh()->isSuspended());
    }

    public function testNoActionIsTakenIfSuspensionStatusIsUnchanged()
    {
        $server = $this->createServerModel();

        $this->getService()->toggle($server, SuspensionService::ACTION_UNSUSPEND);

        $server->refresh();
        $this->assertFalse($server->isSuspended());

        $server->update(['status' => Server::STATUS_SUSPENDED]);
        $this->getService()->toggle($server);

        $server->refresh();
        $this->assertTrue($server->isSuspended());
    }

    public function testExceptionIsThrownIfInvalidActionsArePassed()
    {
        $server = $this->createServerModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected one of: "suspend", "unsuspend". Got: "foo"');

        $this->getService()->toggle($server, 'foo');
    }

    private function getService(): SuspensionService
    {
        return $this->app->make(SuspensionService::class);
    }
}
