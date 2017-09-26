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
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class EnvironmentServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Servers\EnvironmentService
     */
    protected $service;

    /**
     * @var \Pterodactyl\Models\Server
     */
    protected $server;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->server = factory(Server::class)->make([
            'location' => factory(Location::class)->make(),
        ]);

        $this->service = new EnvironmentService($this->repository);
    }

    /**
     * Test that set environment key function returns an instance of the class.
     */
    public function testSettingEnvironmentKeyShouldReturnInstanceOfSelf()
    {
        $instance = $this->service->setEnvironmentKey('TEST_KEY', function () {
            return true;
        });

        $this->assertInstanceOf(EnvironmentService::class, $instance);
    }

    /**
     * Test that environment defaults are returned by the process function.
     */
    public function testProcessShouldReturnDefaultEnvironmentVariablesForAServer()
    {
        $this->repository->shouldReceive('getVariablesWithValues')->with($this->server->id)->once()->andReturn([
            'TEST_VARIABLE' => 'Test Variable',
        ]);

        $response = $this->service->process($this->server);

        $this->assertEquals(count(EnvironmentService::ENVIRONMENT_CASTS) + 1, count($response), 'Assert response contains correct amount of items.');
        $this->assertTrue(is_array($response), 'Assert that response is an array.');

        $this->assertArrayHasKey('TEST_VARIABLE', $response);
        $this->assertEquals('Test Variable', $response['TEST_VARIABLE']);

        foreach (EnvironmentService::ENVIRONMENT_CASTS as $key => $value) {
            $this->assertArrayHasKey($key, $response);
            $this->assertEquals(object_get($this->server, $value), $response[$key]);
        }
    }

    /**
     * Test that variables included at run-time are also included.
     */
    public function testProcessShouldReturnKeySetAtRuntime()
    {
        $this->repository->shouldReceive('getVariablesWithValues')->with($this->server->id)->once()->andReturn([]);

        $response = $this->service->setEnvironmentKey('TEST_VARIABLE', function ($server) {
            return $server->uuidShort;
        })->process($this->server);

        $this->assertTrue(is_array($response), 'Assert response is an array.');
        $this->assertArrayHasKey('TEST_VARIABLE', $response);
        $this->assertEquals($this->server->uuidShort, $response['TEST_VARIABLE']);
    }

    /**
     * Test that duplicate variables provided at run-time override the defaults.
     */
    public function testProcessShouldAllowOverwritingDefaultVariablesWithRuntimeProvided()
    {
        $this->repository->shouldReceive('getVariablesWithValues')->with($this->server->id)->once()->andReturn([]);

        $response = $this->service->setEnvironmentKey('P_SERVER_UUID', function ($server) {
            return 'overwritten';
        })->process($this->server);

        $this->assertTrue(is_array($response), 'Assert response is an array.');
        $this->assertArrayHasKey('P_SERVER_UUID', $response);
        $this->assertEquals('overwritten', $response['P_SERVER_UUID']);
    }

    /**
     * Test that function can run when an ID is provided rather than a server model.
     */
    public function testProcessShouldAcceptAnIntegerInPlaceOfAServerModel()
    {
        $this->repository->shouldReceive('find')->with($this->server->id)->once()->andReturn($this->server);
        $this->repository->shouldReceive('getVariablesWithValues')->with($this->server->id)->once()->andReturn([]);

        $response = $this->service->process($this->server->id);

        $this->assertTrue(is_array($response), 'Assert that response is an array.');
    }
}
