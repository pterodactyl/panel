<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
use Pterodactyl\Services\Users\CreationService;
use Pterodactyl\Services\Subusers\SubuserCreationService;
use Pterodactyl\Services\Subusers\PermissionCreationService;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
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
     * @var \Pterodactyl\Services\Users\CreationService
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

        $this->getFunctionMock('\\Pterodactyl\\Services\\Subusers', 'bin2hex')->expects($this->any())->willReturn('bin2hex');

        $this->connection = m::mock(ConnectionInterface::class);
        $this->daemonRepository = m::mock(DaemonServerRepositoryInterface::class);
        $this->permissionService = m::mock(PermissionCreationService::class);
        $this->subuserRepository = m::mock(SubuserRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->userCreationService = m::mock(CreationService::class);
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

        $this->userRepository->shouldReceive('findWhere')->with([['email', '=', $user->email]])->once()->andReturnNull();
        $this->userCreationService->shouldReceive('handle')->with([
            'email' => $user->email,
            'username' => substr(strtok($user->email, '@'), 0, 8),
            'name_first' => 'Server',
            'name_last' => 'Subuser',
            'root_admin' => false,
        ])->once()->andReturn($user);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->subuserRepository->shouldReceive('create')->with([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'daemonSecret' => 'bin2hex',
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

        $this->userRepository->shouldReceive('findWhere')->with([['email', '=', $user->email]])->once()->andReturn($user);
        $this->subuserRepository->shouldReceive('findCountWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andReturn(0);

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->subuserRepository->shouldReceive('create')->with([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'daemonSecret' => 'bin2hex',
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

        $this->userRepository->shouldReceive('findWhere')->with([['email', '=', $user->email]])->once()->andReturn($user);

        try {
            $this->service->handle($server, $user->email, []);
        } catch (DisplayException $exception) {
            $this->assertInstanceOf(UserIsServerOwnerException::class, $exception);
            $this->assertEquals(trans('admin/exceptions.subusers.user_is_owner'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if the user is already added as a subuser.
     */
    public function testExceptionIsThrownIfUserIsAlreadyASubuser()
    {
        $user = factory(User::class)->make();
        $server = factory(Server::class)->make();

        $this->userRepository->shouldReceive('findWhere')->with([['email', '=', $user->email]])->once()->andReturn($user);
        $this->subuserRepository->shouldReceive('findCountWhere')->with([
            ['user_id', '=', $user->id],
            ['server_id', '=', $server->id],
        ])->once()->andReturn(1);

        try {
            $this->service->handle($server, $user->email, []);
        } catch (DisplayException $exception) {
            $this->assertInstanceOf(ServerSubuserExistsException::class, $exception);
            $this->assertEquals(trans('admin/exceptions.subusers.subuser_exists'), $exception->getMessage());
        }
    }
}
