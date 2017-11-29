<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class EnvironmentServiceTest extends TestCase
{
    const CONFIG_MAPPING = 'pterodactyl.environment_mappings';

    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    private $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
    }

    /**
     * Test that set environment key stores the key into a retreviable array.
     */
    public function testSettingEnvironmentKeyPersistsItInArray()
    {
        $service = $this->getService();

        $service->setEnvironmentKey('TEST_KEY', function () {
            return true;
        });

        $this->assertNotEmpty($service->getEnvironmentKeys());
        $this->assertArrayHasKey('TEST_KEY', $service->getEnvironmentKeys());
    }

    /**
     * Test that environment defaults are returned by the process function.
     */
    public function testProcessShouldReturnDefaultEnvironmentVariablesForAServer()
    {
        $model = $this->getServerModel();
        $this->config->shouldReceive('get')->with(self::CONFIG_MAPPING, [])->once()->andReturn([]);
        $this->repository->shouldReceive('getVariablesWithValues')->with($model->id)->once()->andReturn([
            'TEST_VARIABLE' => 'Test Variable',
        ]);

        $response = $this->getService()->handle($model);
        $this->assertNotEmpty($response);
        $this->assertEquals(4, count($response));
        $this->assertArrayHasKey('TEST_VARIABLE', $response);
        $this->assertSame('Test Variable', $response['TEST_VARIABLE']);
    }

    /**
     * Test that variables included at run-time are also included.
     */
    public function testProcessShouldReturnKeySetAtRuntime()
    {
        $model = $this->getServerModel();
        $this->config->shouldReceive('get')->with(self::CONFIG_MAPPING, [])->once()->andReturn([]);
        $this->repository->shouldReceive('getVariablesWithValues')->with($model->id)->once()->andReturn([]);

        $service = $this->getService();
        $service->setEnvironmentKey('TEST_VARIABLE', function ($server) {
            return $server->uuidShort;
        });

        $response = $service->handle($model);

        $this->assertNotEmpty($response);
        $this->assertArrayHasKey('TEST_VARIABLE', $response);
        $this->assertSame($model->uuidShort, $response['TEST_VARIABLE']);
    }

    /**
     * Test that duplicate variables provided in config override the defaults.
     */
    public function testProcessShouldAllowOverwritingVaraiblesWithConfigurationFile()
    {
        $model = $this->getServerModel();
        $this->repository->shouldReceive('getVariablesWithValues')->with($model->id)->once()->andReturn([]);
        $this->config->shouldReceive('get')->with(self::CONFIG_MAPPING, [])->once()->andReturn([
            'P_SERVER_UUID' => 'name',
        ]);

        $response = $this->getService()->handle($model);

        $this->assertNotEmpty($response);
        $this->assertSame(3, count($response));
        $this->assertArrayHasKey('P_SERVER_UUID', $response);
        $this->assertSame($model->name, $response['P_SERVER_UUID']);
    }

    /**
     * Test that config based environment variables can be done using closures.
     */
    public function testVariablesSetInConfigurationAllowForClosures()
    {
        $model = $this->getServerModel();
        $this->config->shouldReceive('get')->with(self::CONFIG_MAPPING, [])->once()->andReturn([
            'P_SERVER_UUID' => function ($server) {
                return $server->id * 2;
            },
        ]);
        $this->repository->shouldReceive('getVariablesWithValues')->with($model->id)->once()->andReturn([]);

        $response = $this->getService()->handle($model);

        $this->assertNotEmpty($response);
        $this->assertSame(3, count($response));
        $this->assertArrayHasKey('P_SERVER_UUID', $response);
        $this->assertSame($model->id * 2, $response['P_SERVER_UUID']);
    }

    /**
     * Test that duplicate variables provided at run-time override the defaults and those
     * that are defined in the configuration file.
     */
    public function testProcessShouldAllowOverwritingDefaultVariablesWithRuntimeProvided()
    {
        $model = $this->getServerModel();
        $this->config->shouldReceive('get')->with(self::CONFIG_MAPPING, [])->once()->andReturn([
            'P_SERVER_UUID' => 'overwritten-config',
        ]);
        $this->repository->shouldReceive('getVariablesWithValues')->with($model->id)->once()->andReturn([]);

        $service = $this->getService();
        $service->setEnvironmentKey('P_SERVER_UUID', function ($model) {
            return 'overwritten';
        });

        $response = $service->handle($model);

        $this->assertNotEmpty($response);
        $this->assertSame(3, count($response));
        $this->assertArrayHasKey('P_SERVER_UUID', $response);
        $this->assertSame('overwritten', $response['P_SERVER_UUID']);
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Servers\EnvironmentService
     */
    private function getService(): EnvironmentService
    {
        return new EnvironmentService($this->config, $this->repository);
    }

    /**
     * Return a server model with a location relationship to be used in the tests.
     *
     * @return \Pterodactyl\Models\Server
     */
    private function getServerModel(): Server
    {
        return factory(Server::class)->make([
            'location' => factory(Location::class)->make(),
        ]);
    }
}
