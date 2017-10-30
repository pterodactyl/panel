<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Http\Controllers\Admin;

use Mockery as m;
use Tests\TestCase;
use Prologue\Alerts\AlertsMessageBag;
use Tests\Assertions\ControllerAssertionsTrait;
use Pterodactyl\Http\Controllers\Admin\DatabaseController;
use Pterodactyl\Services\Databases\Hosts\HostUpdateService;
use Pterodactyl\Services\Databases\Hosts\HostCreationService;
use Pterodactyl\Services\Databases\Hosts\HostDeletionService;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class DatabaseControllerTest extends TestCase
{
    use ControllerAssertionsTrait;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag|\Mockery\Mock
     */
    private $alert;

    /**
     * @var \Pterodactyl\Services\Databases\Hosts\HostCreationService|\Mockery\Mock
     */
    private $creationService;

    /**
     * @var \Pterodactyl\Services\Databases\Hosts\HostDeletionService|\Mockery\Mock
     */
    private $deletionService;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface|\Mockery\Mock
     */
    private $locationRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \Pterodactyl\Services\Databases\Hosts\HostUpdateService|\Mockery\Mock
     */
    private $updateService;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->alert = m::mock(AlertsMessageBag::class);
        $this->creationService = m::mock(HostCreationService::class);
        $this->deletionService = m::mock(HostDeletionService::class);
        $this->locationRepository = m::mock(LocationRepositoryInterface::class);
        $this->repository = m::mock(DatabaseHostRepositoryInterface::class);
        $this->updateService = m::mock(HostUpdateService::class);
    }

    /**
     * Test the index controller.
     */
    public function testIndexController()
    {
        $this->locationRepository->shouldReceive('getAllWithNodes')->withNoArgs()->once()->andReturn('getAllWithNodes');
        $this->repository->shouldReceive('getWithViewDetails')->withNoArgs()->once()->andReturn('getWithViewDetails');

        $response = $this->getController()->index();

        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('admin.databases.index', $response);
        $this->assertViewHasKey('locations', $response);
        $this->assertViewHasKey('hosts', $response);
        $this->assertViewKeyEquals('locations', 'getAllWithNodes', $response);
        $this->assertViewKeyEquals('hosts', 'getWithViewDetails', $response);
    }

    /**
     * Test the view controller for displaying a specific database host.
     */
    public function testViewController()
    {
        $this->locationRepository->shouldReceive('getAllWithNodes')->withNoArgs()->once()->andReturn('getAllWithNodes');
        $this->repository->shouldReceive('getWithServers')->with(1)->once()->andReturn('getWithServers');

        $response = $this->getController()->view(1);

        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('admin.databases.view', $response);
        $this->assertViewHasKey('locations', $response);
        $this->assertViewHasKey('host', $response);
        $this->assertViewKeyEquals('locations', 'getAllWithNodes', $response);
        $this->assertViewKeyEquals('host', 'getWithServers', $response);
    }

    /**
     * Return an instance of the DatabaseController with mock dependencies.
     *
     * @return \Pterodactyl\Http\Controllers\Admin\DatabaseController
     */
    private function getController(): DatabaseController
    {
        return new DatabaseController(
            $this->alert,
            $this->repository,
            $this->creationService,
            $this->deletionService,
            $this->updateService,
            $this->locationRepository
        );
    }
}
