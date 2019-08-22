<?php

namespace Tests\Unit\Services\DaemonKeys;

use Mockery as m;
use Tests\TestCase;
use App\Models\Node;
use App\Models\User;
use App\Models\DaemonKey;
use Illuminate\Support\Arr;
use GuzzleHttp\Psr7\Response;
use Tests\Traits\MocksRequestException;
use App\Contracts\Repository\DaemonKeyRepositoryInterface;
use App\Services\DaemonKeys\RevokeMultipleDaemonKeysService;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface;

class RevokeMultipleDaemonKeysServiceTest extends TestCase
{
    use MocksRequestException;

    /**
     * @var \App\Contracts\Repository\Daemon\ServerRepositoryInterface|\Mockery\Mock
     */
    private $daemonRepository;

    /**
     * @var \App\Contracts\Repository\DaemonKeyRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
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
        $node = factory(Node::class)->make();
        $key = factory(DaemonKey::class)->make(['user_id' => $user->id]);
        $key->setRelation('node', $node);

        $this->repository->shouldReceive('getKeysForRevocation')->with($user)->once()->andReturn(collect([$key]));
        $this->daemonRepository->shouldReceive('setNode')->with($node)->once()->andReturnSelf();
        $this->daemonRepository->shouldReceive('revokeAccessKey')->with([$key->secret])->once()->andReturn(new Response);

        $this->repository->shouldReceive('deleteKeys')->with([$key->id])->once()->andReturnNull();

        $this->getService()->handle($user);
        $this->assertTrue(true);
    }

    /**
     * Test that an exception thrown by a call to the daemon is handled.
     *
     * @expectedException \App\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function testExceptionThrownFromDaemonCallIsHandled()
    {
        $this->configureExceptionMock();

        $user = factory(User::class)->make();
        $node = factory(Node::class)->make();
        $key = factory(DaemonKey::class)->make(['user_id' => $user->id]);
        $key->setRelation('node', $node);

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
        $node = factory(Node::class)->make();
        $key = factory(DaemonKey::class)->make(['user_id' => $user->id]);
        $key->setRelation('node', $node);

        $this->repository->shouldReceive('getKeysForRevocation')->with($user)->once()->andReturn(collect([$key]));
        $this->daemonRepository->shouldReceive('setNode->revokeAccessKey')->with([$key->secret])->once()->andThrow($this->getExceptionMock());

        $this->repository->shouldReceive('deleteKeys')->with([$key->id])->once()->andReturnNull();

        $service = $this->getService();
        $service->handle($user, true);
        $this->assertNotEmpty($service->getExceptions());
        $this->assertArrayHasKey($node->id, $service->getExceptions());
        $this->assertSame(Arr::get($service->getExceptions(), $node->id), $this->getExceptionMock());
        $this->assertTrue(true);
    }

    /**
     * Return an instance of the service for testing.
     *
     * @return \App\Services\DaemonKeys\RevokeMultipleDaemonKeysService
     */
    private function getService(): RevokeMultipleDaemonKeysService
    {
        return new RevokeMultipleDaemonKeysService($this->repository, $this->daemonRepository);
    }
}
