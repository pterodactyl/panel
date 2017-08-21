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

namespace Tests\Unit\Services\Servers;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Illuminate\Log\Writer;
use phpmock\phpunit\PHPMock;
use Illuminate\Database\DatabaseManager;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Servers\CreationService;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Services\Servers\UsernameGenerationService;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

class CreationServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $allocationRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface
     */
    protected $daemonServerRepository;

    /**
     * @var array
     */
    protected $data = [
        'node_id' => 1,
        'name' => 'SomeName',
        'description' => null,
        'owner_id' => 1,
        'memory' => 128,
        'disk' => 128,
        'swap' => 0,
        'io' => 500,
        'cpu' => 0,
        'allocation_id' => 1,
        'allocation_additional' => [2, 3],
        'environment' => [
            'TEST_VAR_1' => 'var1-value',
        ],
        'service_id' => 1,
        'option_id' => 1,
        'startup' => 'startup-param',
        'docker_image' => 'some/image',
    ];

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * @var \GuzzleHttp\Exception\RequestException
     */
    protected $exception;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $nodeRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    protected $serverVariableRepository;

    /**
     * @var \Pterodactyl\Services\Servers\CreationService
     */
    protected $service;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var \Pterodactyl\Services\Servers\UsernameGenerationService
     */
    protected $usernameService;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService
     */
    protected $validatorService;

    /**
     * @var \Ramsey\Uuid\Uuid
     */
    protected $uuid;

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

        $this->allocationRepository = m::mock(AllocationRepositoryInterface::class);
        $this->daemonServerRepository = m::mock(DaemonServerRepositoryInterface::class);
        $this->database = m::mock(DatabaseManager::class);
        $this->exception = m::mock(RequestException::class);
        $this->nodeRepository = m::mock(NodeRepositoryInterface::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->serverVariableRepository = m::mock(ServerVariableRepositoryInterface::class);
        $this->userRepository = m::mock(UserRepositoryInterface::class);
        $this->usernameService = m::mock(UsernameGenerationService::class);
        $this->validatorService = m::mock(VariableValidatorService::class);
        $this->uuid = m::mock('overload:Ramsey\Uuid\Uuid');
        $this->writer = m::mock(Writer::class);

        $this->getFunctionMock('\\Pterodactyl\\Services\\Servers', 'bin2hex')
            ->expects($this->any())->willReturn('randomstring');

        $this->getFunctionMock('\\Ramsey\\Uuid\\Uuid', 'uuid4')
            ->expects($this->any())->willReturn('s');

        $this->service = new CreationService(
            $this->allocationRepository,
            $this->daemonServerRepository,
            $this->database,
            $this->nodeRepository,
            $this->repository,
            $this->serverVariableRepository,
            $this->userRepository,
            $this->usernameService,
            $this->validatorService,
            $this->writer
        );
    }

    /**
     * Test core functionality of the creation process.
     */
    public function testCreateShouldHitAllOfTheNecessaryServicesAndStoreTheServer()
    {
        $this->validatorService->shouldReceive('isAdmin')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('setFields')->with($this->data['environment'])->once()->andReturnSelf()
            ->shouldReceive('validate')->with($this->data['option_id'])->once()->andReturnSelf();

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->uuid->shouldReceive('uuid4')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('toString')->withNoArgs()->once()->andReturn('uuid-0000');
        $this->usernameService->shouldReceive('generate')->with($this->data['name'], 'randomstring')
            ->once()->andReturn('user_name');

        $this->repository->shouldReceive('create')->with([
            'uuid' => 'uuid-0000',
            'uuidShort' => 'randomstring',
            'node_id' => $this->data['node_id'],
            'name' => $this->data['name'],
            'description' => $this->data['description'],
            'skip_scripts' => false,
            'suspended' => false,
            'owner_id' => $this->data['owner_id'],
            'memory' => $this->data['memory'],
            'swap' => $this->data['swap'],
            'disk' => $this->data['disk'],
            'io' => $this->data['io'],
            'cpu' => $this->data['cpu'],
            'oom_disabled' => false,
            'allocation_id' => $this->data['allocation_id'],
            'service_id' => $this->data['service_id'],
            'option_id' => $this->data['option_id'],
            'pack_id' => null,
            'startup' => $this->data['startup'],
            'daemonSecret' => 'randomstring',
            'image' => $this->data['docker_image'],
            'username' => 'user_name',
            'sftp_password' => null,
        ])->once()->andReturn((object) [
            'node_id' => 1,
            'id' => 1,
        ]);

        $this->allocationRepository->shouldReceive('assignAllocationsToServer')->with(1, [1, 2, 3])->once()->andReturnNull();
        $this->validatorService->shouldReceive('getResults')->withNoArgs()->once()->andReturn([[
            'id' => 1,
            'key' => 'TEST_VAR_1',
            'value' => 'var1-value',
        ]]);

        $this->serverVariableRepository->shouldReceive('insert')->with([[
            'server_id' => 1,
            'variable_id' => 1,
            'variable_value' => 'var1-value',
        ]])->once()->andReturnNull();
        $this->daemonServerRepository->shouldReceive('setNode')->with(1)->once()->andReturnSelf()
            ->shouldReceive('create')->with(1)->once()->andReturnNull();
        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $response = $this->service->create($this->data);

        $this->assertEquals(1, $response->id);
        $this->assertEquals(1, $response->node_id);
    }

    /**
     * Test handling of node timeout or other daemon error.
     */
    public function testExceptionShouldBeThrownIfTheRequestFails()
    {
        $this->validatorService->shouldReceive('isAdmin->setFields->validate->getResults')->once()->andReturn([]);
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->uuid->shouldReceive('uuid4->toString')->once()->andReturn('uuid-0000');
        $this->usernameService->shouldReceive('generate')->once()->andReturn('user_name');
        $this->repository->shouldReceive('create')->once()->andReturn((object) [
            'node_id' => 1,
            'id' => 1,
        ]);

        $this->allocationRepository->shouldReceive('assignAllocationsToServer')->once()->andReturnNull();
        $this->serverVariableRepository->shouldReceive('insert')->with([])->once()->andReturnNull();
        $this->daemonServerRepository->shouldReceive('setNode->create')->once()->andThrow($this->exception);
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnNull();
        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();
        $this->database->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();

        try {
            $this->service->create($this->data);
        } catch (Exception $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(trans('admin/server.exceptions.daemon_exception', [
                'code' => 'E_CONN_REFUSED',
            ]), $exception->getMessage());
        }
    }
}
