<?php

namespace Tests\Unit\Services\Sftp;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Services\Sftp\AuthenticateUsingPasswordService;

class AuthenticateUsingPasswordServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyProviderService|\Mockery\Mock
     */
    private $keyProviderService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
     */
    private $userRepository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->keyProviderService = m::mock(DaemonKeyProviderService::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->userRepository = m::mock(UserRepositoryInterface::class);
    }

    /**
     * Test that an account can be authenticated.
     */
    public function testNonAdminAccountIsAuthenticated()
    {
        $user = factory(User::class)->make(['root_admin' => 0]);
        $server = factory(Server::class)->make(['node_id' => 1, 'owner_id' => $user->id]);

        $this->userRepository->shouldReceive('setColumns')->with(['id', 'root_admin', 'password'])->once()->andReturnSelf();
        $this->userRepository->shouldReceive('findFirstWhere')->with([['username', '=', $user->username]])->once()->andReturn($user);

        $this->repository->shouldReceive('setColumns')->with(['id', 'node_id', 'owner_id', 'uuid'])->once()->andReturnSelf();
        $this->repository->shouldReceive('getByUuid')->with($server->uuidShort)->once()->andReturn($server);

        $this->keyProviderService->shouldReceive('handle')->with($server, $user)->once()->andReturn('server_token');

        $response = $this->getService()->handle($user->username, 'password', 1, $server->uuidShort);
        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('server', $response);
        $this->assertArrayHasKey('token', $response);
        $this->assertSame($server->uuid, $response['server']);
        $this->assertSame('server_token', $response['token']);
    }

    /**
     * Test that an administrative user can access servers that they are not
     * set as the owner of.
     */
    public function testAdminAccountIsAuthenticated()
    {
        $user = factory(User::class)->make(['root_admin' => 1]);
        $server = factory(Server::class)->make(['node_id' => 1, 'owner_id' => $user->id + 1]);

        $this->userRepository->shouldReceive('setColumns')->with(['id', 'root_admin', 'password'])->once()->andReturnSelf();
        $this->userRepository->shouldReceive('findFirstWhere')->with([['username', '=', $user->username]])->once()->andReturn($user);

        $this->repository->shouldReceive('setColumns')->with(['id', 'node_id', 'owner_id', 'uuid'])->once()->andReturnSelf();
        $this->repository->shouldReceive('getByUuid')->with($server->uuidShort)->once()->andReturn($server);

        $this->keyProviderService->shouldReceive('handle')->with($server, $user)->once()->andReturn('server_token');

        $response = $this->getService()->handle($user->username, 'password', 1, $server->uuidShort);
        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('server', $response);
        $this->assertArrayHasKey('token', $response);
        $this->assertSame($server->uuid, $response['server']);
        $this->assertSame('server_token', $response['token']);
    }

    /**
     * Test exception gets thrown if no server is passed into the function.
     *
     * @expectedException \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function testExceptionIsThrownIfNoServerIsProvided()
    {
        $this->getService()->handle('username', 'password', 1);
    }

    /**
     * Test that an exception is thrown if the user account exists but the wrong
     * credentials are passed.
     *
     * @expectedException \Illuminate\Auth\AuthenticationException
     */
    public function testExceptionIsThrownIfUserDetailsAreIncorrect()
    {
        $user = factory(User::class)->make();

        $this->userRepository->shouldReceive('setColumns')->with(['id', 'root_admin', 'password'])->once()->andReturnSelf();
        $this->userRepository->shouldReceive('findFirstWhere')->with([['username', '=', $user->username]])->once()->andReturn($user);

        $this->getService()->handle($user->username, 'wrongpassword', 1, '1234');
    }

    /**
     * Test that an exception is thrown if no user account is found.
     *
     * @expectedException \Illuminate\Auth\AuthenticationException
     */
    public function testExceptionIsThrownIfNoUserAccountIsFound()
    {
        $this->userRepository->shouldReceive('setColumns')->with(['id', 'root_admin', 'password'])->once()->andReturnSelf();
        $this->userRepository->shouldReceive('findFirstWhere')->with([['username', '=', 'something']])->once()->andThrow(new RecordNotFoundException);

        $this->getService()->handle('something', 'password', 1, '1234');
    }

    /**
     * Test that an exception is thrown if the user is not the owner of the server
     * and is not an administrator.
     *
     * @expectedException \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function testExceptionIsThrownIfUserDoesNotOwnServer()
    {
        $user = factory(User::class)->make(['root_admin' => 0]);
        $server = factory(Server::class)->make(['node_id' => 1, 'owner_id' => $user->id + 1]);

        $this->userRepository->shouldReceive('setColumns')->with(['id', 'root_admin', 'password'])->once()->andReturnSelf();
        $this->userRepository->shouldReceive('findFirstWhere')->with([['username', '=', $user->username]])->once()->andReturn($user);

        $this->repository->shouldReceive('setColumns')->with(['id', 'node_id', 'owner_id', 'uuid'])->once()->andReturnSelf();
        $this->repository->shouldReceive('getByUuid')->with($server->uuidShort)->once()->andReturn($server);

        $this->getService()->handle($user->username, 'password', 1, $server->uuidShort);
    }

    /**
     * Test that an exception is thrown if the requested server does not belong to
     * the node that the request is made from.
     *
     * @expectedException \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function testExceptionIsThrownIfServerDoesNotExistOnCurrentNode()
    {
        $user = factory(User::class)->make(['root_admin' => 0]);
        $server = factory(Server::class)->make(['node_id' => 2, 'owner_id' => $user->id]);

        $this->userRepository->shouldReceive('setColumns')->with(['id', 'root_admin', 'password'])->once()->andReturnSelf();
        $this->userRepository->shouldReceive('findFirstWhere')->with([['username', '=', $user->username]])->once()->andReturn($user);

        $this->repository->shouldReceive('setColumns')->with(['id', 'node_id', 'owner_id', 'uuid'])->once()->andReturnSelf();
        $this->repository->shouldReceive('getByUuid')->with($server->uuidShort)->once()->andReturn($server);

        $this->getService()->handle($user->username, 'password', 1, $server->uuidShort);
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Sftp\AuthenticateUsingPasswordService
     */
    private function getService(): AuthenticateUsingPasswordService
    {
        return new AuthenticateUsingPasswordService($this->keyProviderService, $this->repository, $this->userRepository);
    }
}
