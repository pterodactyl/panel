<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Services\Servers\EnvironmentService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Services\Servers\ServerConfigurationStructureService;

class ServerConfigurationStructureServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Services\Servers\EnvironmentService|\Mockery\Mock
     */
    private $environment;

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

        $this->environment = m::mock(EnvironmentService::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
    }

    /**
     * Test that a configuration is returned in the proper format when passed a
     * server model that is missing required relationships.
     */
    public function testCorrectStructureIsReturned()
    {
        /** @var \Pterodactyl\Models\Server $model */
        $model = factory(Server::class)->make();
        $model->setRelation('allocation', factory(Allocation::class)->make());
        $model->setRelation('allocations', collect(factory(Allocation::class)->times(2)->make()));
        $model->setRelation('egg', factory(Egg::class)->make());

        $this->environment->expects('handle')->with($model)->andReturn(['environment_array']);

        $response = $this->getService()->handle($model);
        $this->assertNotEmpty($response);
        $this->assertArrayNotHasKey('user', $response);
        $this->assertArrayNotHasKey('keys', $response);

        $this->assertArrayHasKey('uuid', $response);
        $this->assertArrayHasKey('suspended', $response);
        $this->assertArrayHasKey('environment', $response);
        $this->assertArrayHasKey('invocation', $response);
        $this->assertArrayHasKey('skip_egg_scripts', $response);
        $this->assertArrayHasKey('build', $response);
        $this->assertArrayHasKey('container', $response);
        $this->assertArrayHasKey('allocations', $response);

        $this->assertSame([
            'default' => [
                'ip' => $model->allocation->ip,
                'port' => $model->allocation->port,
            ],
            'mappings' => $model->getAllocationMappings(),
        ], $response['allocations']);

        $this->assertSame([
            'memory_limit' => $model->memory,
            'swap' => $model->swap,
            'io_weight' => $model->io,
            'cpu_limit' => $model->cpu,
            'threads' => $model->threads,
            'disk_space' => $model->disk,
        ], $response['build']);

        $this->assertSame([
            'image' => $model->image,
            'oom_disabled' => $model->oom_disabled,
            'requires_rebuild' => false,
        ], $response['container']);

        $this->assertSame($model->uuid, $response['uuid']);
        $this->assertSame($model->suspended, $response['suspended']);
        $this->assertSame(['environment_array'], $response['environment']);
        $this->assertSame($model->startup, $response['invocation']);
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Servers\ServerConfigurationStructureService
     */
    private function getService(): ServerConfigurationStructureService
    {
        return new ServerConfigurationStructureService($this->environment);
    }
}
