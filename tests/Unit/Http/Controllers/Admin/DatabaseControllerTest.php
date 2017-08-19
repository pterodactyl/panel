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

namespace Tests\Unit\Http\Controllers\Admin;

use Mockery as m;
use Tests\TestCase;
use Prologue\Alerts\AlertsMessageBag;
use Tests\Assertions\ControllerAssertionsTrait;
use Pterodactyl\Services\Database\DatabaseHostService;
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
     * @var \Pterodactyl\Services\Database\DatabaseHostService
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
        $this->service = m::mock(DatabaseHostService::class);

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

        $view = $this->controller->index();

        $this->assertViewNameEquals('admin.databases.index', $view);
        $this->assertViewHasKey('locations', $view);
        $this->assertViewHasKey('hosts', $view);
        $this->assertViewKeyEquals('locations', 'getAllWithNodes', $view);
        $this->assertViewKeyEquals('hosts', 'getWithViewDetails', $view);
    }

    /**
     * Test the view controller for displaying a specific database host.
     */
    public function testViewController()
    {
        $this->locationRepository->shouldReceive('getAllWithNodes')->withNoArgs()->once()->andReturn('getAllWithNodes');
        $this->repository->shouldReceive('getWithServers')->with(1)->once()->andReturn('getWithServers');

        $view = $this->controller->view(1);

        $this->assertViewNameEquals('admin.databases.view', $view);
        $this->assertViewHasKey('locations', $view);
        $this->assertViewHasKey('host', $view);
        $this->assertViewKeyEquals('locations', 'getAllWithNodes', $view);
        $this->assertViewKeyEquals('host', 'getWithServers', $view);
    }
}
