<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Location;
use Illuminate\Support\Collection;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class EnvironmentServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();
        config()->set('pterodactyl.environment_variables', []);
    }

    /**
     * Test that set environment key stores the key into a retrievable array.
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
        $model = $this->getServerModel([
            'TEST_VARIABLE' => factory(EggVariable::class)->make([
                'id' => 987,
                'env_variable' => 'TEST_VARIABLE',
                'default_value' => 'Test Variable',
            ]),
        ]);

        $response = $this->getService()->handle($model);
        $this->assertNotEmpty($response);
        $this->assertCount(4, $response);
        $this->assertArrayHasKey('TEST_VARIABLE', $response);
        $this->assertSame('Test Variable', $response['TEST_VARIABLE']);
    }

    /**
     * Test that variables included at run-time are also included.
     */
    public function testProcessShouldReturnKeySetAtRuntime()
    {
        $model = $this->getServerModel([]);
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
    public function testProcessShouldAllowOverwritingVariablesWithConfigurationFile()
    {
        config()->set('pterodactyl.environment_variables', [
            'P_SERVER_UUID' => 'name',
        ]);

        $model = $this->getServerModel([]);
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
        config()->set('pterodactyl.environment_variables', [
            'P_SERVER_UUID' => function ($server) {
                return $server->id * 2;
            },
        ]);

        $model = $this->getServerModel([]);
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
        config()->set('pterodactyl.environment_variables', [
            'P_SERVER_UUID' => 'overwritten-config',
        ]);

        $model = $this->getServerModel([]);
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
        return new EnvironmentService;
    }

    /**
     * Return a server model with a location relationship to be used in the tests.
     *
     * @param array $variables
     * @return \Pterodactyl\Models\Server
     */
    private function getServerModel(array $variables): Server
    {
        /** @var \Pterodactyl\Models\Server $server */
        $server = factory(Server::class)->make([
            'id' => 123,
            'location' => factory(Location::class)->make(),
        ]);

        $server->setRelation('variables', Collection::make($variables));

        return $server;
    }
}
