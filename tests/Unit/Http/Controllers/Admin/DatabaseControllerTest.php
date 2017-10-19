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
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;
use Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface;

class DatabaseControllerTest extends TestCase
{
    use ControllerAssertionsTrait;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Pterodactyl\Http\Controllers\Admin\DatabaseController
     */
    protected $controller;

    /**
     * @var \Pterodactyl\Contracts\Repository\LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseHostRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Databases\HostsUpdateService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->alert = m::mock(AlertsMessageBag::class);
        $this->locationRepository = m::mock(LocationRepositoryInterface::class);
        $this->repository = m::mock(DatabaseHostRepositoryInterface::class);
        $this->service = m::mock(HostUpdateService::class);

        $this->controller = new DatabaseController(
            $this->alert,
            $this->repository,
            $this->service,
            $this->locationRepository
        );
    }

    /**
     * Test the index controller.
     */
    public function testIndexController()
    {
        $this->locationRepository->shouldReceive('getAllWithNodes')->withNoArgs()->once()->andReturn('getAllWithNodes');
        $this->repository->shouldReceive('getWithViewDetails')->withNoArgs()->once()->andReturn('getWithViewDetails');

        $response = $this->controller->index();

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

        $response = $this->controller->view(1);

        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('admin.databases.view', $response);
        $this->assertViewHasKey('locations', $response);
        $this->assertViewHasKey('host', $response);
        $this->assertViewKeyEquals('locations', 'getAllWithNodes', $response);
        $this->assertViewKeyEquals('host', 'getWithServers', $response);
    }
}
