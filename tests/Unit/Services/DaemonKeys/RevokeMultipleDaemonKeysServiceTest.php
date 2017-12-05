<?php

namespace Tests\Unit\Services\DaemonKeys;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\DaemonKey;
use Tests\Traits\MocksRequestException;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;
use Pterodactyl\Services\DaemonKeys\RevokeMultipleDaemonKeysService;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface;

class RevokeMultipleDaemonKeysServiceTest extends TestCase
{
    use MocksRequestException;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface|\Mockery\Mock
     */
    private $daemonRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->daemonRepository = m::mock(ServerRepositoryInterface::class);
        $this->repository = m::mock(DaemonKeyRepositoryInterface::class);
    }

    /**
     * Test that keys can be successfully revoked.
     */
    public function testSuccessfulKeyRevocation()
    {
        $user = factory(User::class)->make();
        $server = factory(Server::class)->make();
        $key = factory(DaemonKey::class)->make(['user_id' => $user->id]);
        $key->setRelation('server', $server);

        $this->repository->shouldReceive('getKeysForRevocation')->with($user)->once()->andReturn(collect([$key]));
        $this->daemonRepository->shouldReceive('setNode')->with($server->node_id)->once()->andReturnSelf();
        $this->daemonRepository->shouldReceive('revokeAccessKey')->with([$key->secret])->once()->andReturnNull();

        $this->repository->shouldReceive('deleteKeys')->with([$key->id])->once()->andReturnNull();

        $this->getService()->handle($user);
        $this->assertTrue(true);
    }

    /**
     * Test that an exception thrown by a call to the daemon is handled.
     *
     * @expectedException \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function testExceptionThrownFromDaemonCallIsHandled()
    {
        $this->configureExceptionMock();

        $user = factory(User::class)->make();
        $server = factory(Server::class)->make();
        $key = factory(DaemonKey::class)->make(['user_id' => $user->id]);
        $key->setRelation('server', $server);

        $this->repository->shouldReceive('getKeysForRevocation')->with($user)->once()->andReturn(collect([$key]));
        $this->daemonRepository->shouldReceive('setNode->revokeAccessKey')->with([$key->secret])->once()->andThrow($this->getExceptionMock());

        $this->getService()->handle($user);
    }

    /**
     * Test that the behavior for handling exceptions that should not be thrown
     * immediately is working correctly and adds them to the array.
     */
    public function testIgnoredExceptionsAreHandledProperly()
    {
        $this->configureExceptionMock();

        $user = factory(User::class)->make();
        $server = factory(Server::class)->make();
        $key = factory(DaemonKey::class)->make(['user_id' => $user->id]);
        $key->setRelation('server', $server);

        $this->repository->shouldReceive('getKeysForRevocation')->with($user)->once()->andReturn(collect([$key]));
        $this->daemonRepository->shouldReceive('setNode->revokeAccessKey')->with([$key->secret])->once()->andThrow($this->getExceptionMock());

        $this->repository->shouldReceive('deleteKeys')->with([$key->id])->once()->andReturnNull();

        $service = $this->getService();
        $service->handle($user, true);
        $this->assertNotEmpty($service->getExceptions());
        $this->assertArrayHasKey($server->node_id, $service->getExceptions());
        $this->assertSame(array_get($service->getExceptions(), $server->node_id), $this->getExceptionMock());
        $this->assertTrue(true);
    }

    /**
     * Return an instance of the service for testing.
     *
     * @return \Pterodactyl\Services\DaemonKeys\RevokeMultipleDaemonKeysService
     */
    private function getService(): RevokeMultipleDaemonKeysService
    {
        return new RevokeMultipleDaemonKeysService($this->repository, $this->daemonRepository);
    }
}
