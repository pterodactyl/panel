<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Tests\Traits\MocksUuids;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Services\Servers\UsernameGenerationService;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepositoryInterface;

/**
 * @preserveGlobalState disabled
 */
class ServerCreationServiceTest extends TestCase
{
    use MocksUuids, PHPMock;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface|\Mockery\Mock
     */
    protected $allocationRepository;

    /**
     * @var \Pterodactyl\Services\Servers\ServerConfigurationStructureService|\Mockery\Mock
     */
    protected $configurationStructureService;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface|\Mockery\Mock
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
        'nest_id' => 1,
        'egg_id' => 1,
        'startup' => 'startup-param',
        'docker_image' => 'some/image',
    ];

    /**
     * @var \GuzzleHttp\Exception\RequestException|\Mockery\Mock
     */
    protected $exception;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface|\Mockery\Mock
     */
    protected $nodeRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface|\Mockery\Mock
     */
    protected $serverVariableRepository;

    /**
     * @var \Pterodactyl\Services\Servers\ServerCreationService
     */
    protected $service;

    /**
     * @var \Pterodactyl\Contracts\Repository\UserRepositoryInterface|\Mockery\Mock
     */
    protected $userRepository;

    /**
     * @var \Pterodactyl\Services\Servers\UsernameGenerationService|\Mockery\Mock
     */
    protected $usernameService;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService|\Mockery\Mock
     */
    protected $validatorService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->allocationRepository = m::mock(AllocationRepositoryInterface::class);
        $this->configurationStructureService = m::mock(ServerConfigurationStructureService::class);
        $this->connection = m::mock(ConnectionInterface::class);
        $this->daemonServerRepository = m::mock(DaemonServerRepositoryInterface::class);
        $this->exception = m::mock(RequestException::class);
        $this->nodeRepository = m::mock(NodeRepositoryInterface::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->serverVariableRepository = m::mock(ServerVariableRepositoryInterface::class);
        $this->userRepository = m::mock(UserRepositoryInterface::class);
        $this->usernameService = m::mock(UsernameGenerationService::class);
        $this->validatorService = m::mock(VariableValidatorService::class);

        $this->getFunctionMock('\\Pterodactyl\\Services\\Servers', 'str_random')
            ->expects($this->any())->willReturn('random_string');

        $this->service = new ServerCreationService(
            $this->allocationRepository,
            $this->connection,
            $this->daemonServerRepository,
            $this->nodeRepository,
            $this->configurationStructureService,
            $this->repository,
            $this->serverVariableRepository,
            $this->userRepository,
            $this->usernameService,
            $this->validatorService
        );
    }

    /**
     * Test core functionality of the creation process.
     */
    public function testCreateShouldHitAllOfTheNecessaryServicesAndStoreTheServer()
    {
        $this->validatorService->shouldReceive('isAdmin')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('setFields')->with($this->data['environment'])->once()->andReturnSelf()
            ->shouldReceive('validate')->with($this->data['egg_id'])->once()->andReturnSelf();

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->usernameService->shouldReceive('generate')->with($this->data['name'], 'random_string')
            ->once()->andReturn('user_name');

        $this->repository->shouldReceive('create')->with(m::subset([
            'uuid' => $this->getKnownUuid(),
            'node_id' => $this->data['node_id'],
            'owner_id' => 1,
            'nest_id' => 1,
            'egg_id' => 1,
        ]))->once()->andReturn((object) [
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

        $this->configurationStructureService->shouldReceive('handle')->with(1)->once()->andReturn(['test' => 'struct']);

        $this->daemonServerRepository->shouldReceive('setNode')->with(1)->once()->andReturnSelf()
            ->shouldReceive('create')->with(['test' => 'struct'], ['start_on_completion' => false])->once()->andReturnNull();
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

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
        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->usernameService->shouldReceive('generate')->once()->andReturn('user_name');
        $this->repository->shouldReceive('create')->once()->andReturn((object) [
            'node_id' => 1,
            'id' => 1,
        ]);

        $this->allocationRepository->shouldReceive('assignAllocationsToServer')->once()->andReturnNull();
        $this->serverVariableRepository->shouldReceive('insert')->with([])->once()->andReturnNull();
        $this->configurationStructureService->shouldReceive('handle')->once()->andReturnNull();
        $this->daemonServerRepository->shouldReceive('setNode->create')->once()->andThrow($this->exception);
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnNull();
        $this->connection->shouldReceive('rollBack')->withNoArgs()->once()->andReturnNull();

        try {
            $this->service->create($this->data);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(DaemonConnectionException::class, $exception);
        }
    }
}
