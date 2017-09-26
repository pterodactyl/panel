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
use Illuminate\Log\Writer;
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
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;
use Pterodactyl\Exceptions\Service\Subuser\UserIsServerOwnerException;
use Pterodactyl\Exceptions\Service\Subuser\ServerSubuserExistsException;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class SubuserCreationServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonRepository;

    /**
     * @var \Pterodactyl\Services\Subusers\PermissionCreationService
     */
    protected $permissionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $subuserRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Services\Subusers\SubuserCreationService
     */
    protected $service;

    /**
     * @var \Pterodactyl\Services\Users\UserCreationService
     */
    protected $userCreationService;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var \Illuminate\Log\Writer
     */
    protected $writer;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->getFunctionMock('\\Pterodactyl\\Services\\Subusers', 'str_random')->expects($this->any())->willReturn('random_string');

        $this->connection = m::mock(ConnectionInterface::class);
        $this->daemonRepository = m::mock(DaemonServerRepositoryInterface::class);
        $this->permissionService = m::mock(PermissionCreationService::class);
        $this->subuserRepository = m::mock(SubuserRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->userCreationService = m::mock(UserCreationService::class);
        $this->userRepository = m::mock(UserRepositoryInterface::class);
        $this->writer = m::mock(Writer::class);

        $this->service = new SubuserCreationService(
            $this->connection,
            $this->userCreationService,
            $this->daemonRepository,
            $this->permissionService,
            $this->serverRepository,
            $this->subuserRepository,
            $this->userRepository,
            $this->writer
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

        $this->subuserRepository->shouldReceive('create')->with([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'daemonSecret' => 'random_string',
        ])->once()->andReturn($subuser);

        $this->permissionService->shouldReceive('handle')->with($subuser->id, array_keys($permissions))->once()
            ->andReturn(['s:get', 's:console', 'test:1']);

        $this->daemonRepository->shouldReceive('setNode')->with($server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($server->uuid)->once()->andReturnSelf()
            ->shouldReceive('setSubuserKey')->with($subuser->daemonSecret, ['s:get', 's:console', 'test:1'])->once()->andReturnSelf();
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
        $permissions = ['view-sftp', 'reset-sftp'];
        $server = factory(Server::class)->make();
        $user = factory(User::class)->make();
        $subuser = factory(Subuser::class)->make(['user_id' => $user->id, 'server_id' => $server->id]);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->userRepository->shouldReceive('findFirstWhere')->with([['email', '=', $user->email]])->once()->andReturn($user);
        $this->subuserRepository->shouldReceive('findCountWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andReturn(0);

        $this->subuserRepository->shouldReceive('create')->with([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'daemonSecret' => 'random_string',
        ])->once()->andReturn($subuser);

        $this->permissionService->shouldReceive('handle')->with($subuser->id, $permissions)->once()
            ->andReturn(['s:get', 's:console', 's:set-password']);

        $this->daemonRepository->shouldReceive('setNode')->with($server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($server->uuid)->once()->andReturnSelf()
            ->shouldReceive('setSubuserKey')->with($subuser->daemonSecret, ['s:get', 's:console', 's:set-password'])->once()->andReturnSelf();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->handle($server, $user->email, $permissions);

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
