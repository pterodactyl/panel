<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Subusers;

use Mockery as m;
use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Subuser;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Users\UserCreationService;
use Pterodactyl\Services\Subusers\SubuserCreationService;
use Pterodactyl\Services\Subusers\PermissionCreationService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException;
use Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException;

class SubuserCreationServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Services\DaemonKeys\DaemonKeyCreationService|\Mockery\Mock
     */
    protected $keyCreationService;

    /**
     * @var \Pterodactyl\Services\Subusers\PermissionCreationService|\Mockery\Mock
     */
    protected $permissionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface|\Mockery\Mock
     */
    protected $subuserRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserCreationService
     */
    protected $service;

    /**
     * @var \Pterodactyl\Services\Users\UserCreationService|\Mockery\Mock
     */
    protected $userCreationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
     */
    protected $userRepository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Subusers', 'str_random')->expects($this->any())->willReturn('random_string');

        $this->connection = m::mock(ConnectionInterface::class);
        $this->keyCreationService = m::mock(DaemonKeyCreationService::class);
        $this->permissionService = m::mock(PermissionCreationService::class);
        $this->subuserRepository = m::mock(SubuserRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->userCreationService = m::mock(UserCreationService::class);
        $this->userRepository = m::mock(UserRepositoryInterface::class);

        $this->service = new SubuserCreationService(
            $this->connection,
            $this->keyCreationService,
            $this->permissionService,
            $this->serverRepository,
            $this->subuserRepository,
            $this->userCreationService,
            $this->userRepository
        );
    }

    /**
     * Test that a user without an existing account can be added as a subuser.
     */
    public function testAccountIsCreatedForNewUser()
    {
        $permissions = ['test-1' => 'test:1', 'test-2' => null];
        $server = factory(Server::class)->make();
        $user = factory(User::class)->make();
        $subuser = factory(Subuser::class)->make(['user_id' => $user->id, 'server_id' => $server->id]);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->userRepository->shouldReceive('findFirstWhere')->with([['email', '=', $user->email]])->once()->andThrow(new RecordNotFoundException);
        $this->userCreationService->shouldReceive('handle')->with([
            'email' => $user->email,
            'username' => substr(strtok($user->email, '@'), 0, 8) . '_' . 'random_string',
            'name_first' => 'Server',
            'name_last' => 'Subuser',
            'root_admin' => false,
        ])->once()->andReturn($user);

        $this->subuserRepository->shouldReceive('create')->with(['user_id' => $user->id, 'server_id' => $server->id])
            ->once()->andReturn($subuser);
        $this->keyCreationService->shouldReceive('handle')->with($server->id, $user->id)->once()->andReturnNull();
        $this->permissionService->shouldReceive('handle')->with($subuser->id, array_keys($permissions))->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle($server, $user->email, array_keys($permissions));
        $this->assertInstanceOf(Subuser::class, $response);
        $this->assertSame($subuser, $response);
    }

    /**
     * Test that an existing user can be added as a subuser.
     */
    public function testExistingUserCanBeAddedAsASubuser()
    {
        $permissions = ['view-sftp'];
        $server = factory(Server::class)->make();
        $user = factory(User::class)->make();
        $subuser = factory(Subuser::class)->make(['user_id' => $user->id, 'server_id' => $server->id]);

        $this->serverRepository->shouldReceive('find')->with($server->id)->once()->andReturn($server);
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->userRepository->shouldReceive('findFirstWhere')->with([['email', '=', $user->email]])->once()->andReturn($user);
        $this->subuserRepository->shouldReceive('findCountWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andReturn(0);

        $this->subuserRepository->shouldReceive('create')->with(['user_id' => $user->id, 'server_id' => $server->id])
            ->once()->andReturn($subuser);
        $this->keyCreationService->shouldReceive('handle')->with($server->id, $user->id)->once()->andReturnNull();
        $this->permissionService->shouldReceive('handle')->with($subuser->id, $permissions)->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle($server->id, $user->email, $permissions);
        $this->assertInstanceOf(Subuser::class, $response);
        $this->assertSame($subuser, $response);
    }

    /**
     * Test that an exception gets thrown if the subuser is actually the server owner.
     */
    public function testExceptionIsThrownIfUserIsServerOwner()
    {
        $user = factory(User::class)->make();
        $server = factory(Server::class)->make(['owner_id' => $user->id]);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->userRepository->shouldReceive('findFirstWhere')->with([['email', '=', $user->email]])->once()->andReturn($user);

        try {
            $this->service->handle($server, $user->email, []);
        } catch (DisplayException $exception) {
            $this->assertInstanceOf(UserIsServerOwnerException::class, $exception);
            $this->assertEquals(trans('exceptions.subusers.user_is_owner'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if the user is already added as a subuser.
     */
    public function testExceptionIsThrownIfUserIsAlreadyASubuser()
    {
        $user = factory(User::class)->make();
        $server = factory(Server::class)->make();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->userRepository->shouldReceive('findFirstWhere')->with([['email', '=', $user->email]])->once()->andReturn($user);
        $this->subuserRepository->shouldReceive('findCountWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andReturn(1);

        try {
            $this->service->handle($server, $user->email, []);
        } catch (DisplayException $exception) {
            $this->assertInstanceOf(ServerSubuserExistsException::class, $exception);
            $this->assertEquals(trans('exceptions.subusers.subuser_exists'), $exception->getMessage());
        }
    }
}
