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
    public function setUp()
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
        $model = factory(Server::class)->make();
        $model->setRelation('pack', null);
        $model->setRelation('allocation', factory(Allocation::class)->make());
        $model->setRelation('allocations', collect(factory(Allocation::class)->times(2)->make()));
        $model->setRelation('egg', factory(Egg::class)->make());

        $portListing = $model->allocations->groupBy('ip')->map(function ($item) {
            return $item->pluck('port');
        })->toArray();

        $this->repository->shouldReceive('getDataForCreation')->with($model)->once()->andReturn($model);
        $this->environment->shouldReceive('handle')->with($model)->once()->andReturn(['environment_array']);

        $response = $this->getService()->handle($model);
        $this->assertNotEmpty($response);
        $this->assertArrayNotHasKey('user', $response);
        $this->assertArrayNotHasKey('keys', $response);
        $this->assertArrayHasKey('uuid', $response);
        $this->assertArrayHasKey('build', $response);
        $this->assertArrayHasKey('service', $response);
        $this->assertArrayHasKey('rebuild', $response);
        $this->assertArrayHasKey('suspended', $response);

        $this->assertArraySubset([
            'default' => [
                'ip' => $model->allocation->ip,
                'port' => $model->allocation->port,
            ],
        ], $response['build'], true, 'Assert server default allocation is correct.');
        $this->assertArraySubset(['ports' => $portListing], $response['build'], true, 'Assert server ports are correct.');
        $this->assertArraySubset([
            'env' => ['environment_array'],
            'swap' => (int) $model->swap,
            'io' => (int) $model->io,
            'cpu' => (int) $model->cpu,
            'disk' => (int) $model->disk,
            'image' => $model->image,
        ], $response['build'], true, 'Assert server build data is correct.');

        $this->assertArraySubset([
            'egg' => $model->egg->uuid,
            'pack' => null,
            'skip_scripts' => $model->skip_scripts,
        ], $response['service']);

        $this->assertFalse($response['rebuild']);
        $this->assertSame((int) $model->suspended, $response['suspended']);
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Servers\ServerConfigurationStructureService
     */
    private function getService(): ServerConfigurationStructureService
    {
        return new ServerConfigurationStructureService($this->repository, $this->environment);
    }
}
