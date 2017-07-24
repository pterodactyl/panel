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

use Illuminate\Log\Writer;
use Mockery as m;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\UserRepositoryInterface;
use Pterodactyl\Services\Servers\CreationService;
use Pterodactyl\Services\Servers\UsernameGenerationService;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;
use Illuminate\Database\DatabaseManager;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;
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
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

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
        $data = [
            'node_id' => 1,
            'name' => 'SomeName',
            'description' => null,
            'user_id' => 1,
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

        $this->validatorService->shouldReceive('setAdmin')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('setFields')->with($data['environment'])->once()->andReturnSelf()
            ->shouldReceive('validate')->with($data['option_id'])->once()->andReturnSelf();

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->uuid->shouldReceive('uuid4')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('toString')->withNoArgs()->once()->andReturn('uuid-0000');
        $this->usernameService->shouldReceive('generate')->with($data['name'], 'randomstring')
            ->once()->andReturn('user_name');

        $this->repository->shouldReceive('create')->with([
            'uuid' => 'uuid-0000',
            'uuidShort' => 'randomstring',
            'node_id' => $data['node_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'skip_scripts' => false,
            'suspended' => false,
            'owner_id' => $data['user_id'],
            'memory' => $data['memory'],
            'swap' => $data['swap'],
            'disk' => $data['disk'],
            'io' => $data['io'],
            'cpu' => $data['cpu'],
            'oom_disabled' => false,
            'allocation_id' => $data['allocation_id'],
            'service_id' => $data['service_id'],
            'option_id' => $data['option_id'],
            'pack_id' => null,
            'startup' => $data['startup'],
            'daemonSecret' => 'randomstring',
            'image' => $data['docker_image'],
            'username' => 'user_name',
            'sftp_password' => null,
        ])->once()->andReturn((object) [
            'node_id' => 1,
            'id' => 1,
        ]);

        $this->allocationRepository->shouldReceive('assignAllocationsToServer')->with(1, [1, 2, 3]);
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

        $response = $this->service->create($data);

        $this->assertEquals(1, $response->id);
        $this->assertEquals(1, $response->node_id);
    }
}
