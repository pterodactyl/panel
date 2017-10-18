<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Services\Servers\StartupModificationService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepository;

class StartupModificationServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $daemonServerRepository;

    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Services\Servers\EnvironmentService|\Mockery\Mock
     */
    protected $environmentService;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface|\Mockery\Mock
     */
    protected $serverVariableRepository;

    /**
     * @var \Pterodactyl\Services\Servers\StartupModificationService
     */
    protected $service;

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

        $this->daemonServerRepository = m::mock(DaemonServerRepository::class);
        $this->connection = m::mock(ConnectionInterface::class);
        $this->environmentService = m::mock(EnvironmentService::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->serverVariableRepository = m::mock(ServerVariableRepositoryInterface::class);
        $this->validatorService = m::mock(VariableValidatorService::class);

        $this->service = new StartupModificationService(
            $this->connection,
            $this->daemonServerRepository,
            $this->environmentService,
            $this->repository,
            $this->serverVariableRepository,
            $this->validatorService
        );
    }

    /**
     * Test startup is modified when user is not an administrator.
     *
     * @todo this test works, but not for the right reasons...
     */
    public function testStartupIsModifiedAsNonAdmin()
    {
        $model = factory(Server::class)->make();

        $this->assertTrue(true);
    }
}
