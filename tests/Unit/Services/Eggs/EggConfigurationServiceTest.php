<?php

namespace Tests\Unit\Services\Eggs;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Eggs\EggConfigurationService;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;

class EggConfigurationServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Eggs\EggConfigurationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(EggRepositoryInterface::class);

        $this->service = new EggConfigurationService($this->repository);
    }

    /**
     * Test that the correct array is returned.
     */
    public function testCorrectArrayIsReturned()
    {
        $egg = factory(Egg::class)->make([
            'config_startup' => '{"test": "start"}',
            'config_stop' => 'test',
            'config_files' => '{"test": "file"}',
            'config_logs' => '{"test": "logs"}',
        ]);

        $response = $this->service->handle($egg);
        $this->assertNotEmpty($response);
        $this->assertTrue(is_array($response), 'Assert response is an array.');
        $this->assertArrayHasKey('startup', $response);
        $this->assertArrayHasKey('stop', $response);
        $this->assertArrayHasKey('configs', $response);
        $this->assertArrayHasKey('log', $response);
        $this->assertArrayHasKey('query', $response);
        $this->assertEquals('start', object_get($response['startup'], 'test'));
        $this->assertEquals('test', 'test');
        $this->assertEquals('file', object_get($response['configs'], 'test'));
        $this->assertEquals('logs', object_get($response['log'], 'test'));
        $this->assertEquals('none', $response['query']);
    }

    /**
     * Test that an integer referencing a model can be passed in place of the model.
     */
    public function testFunctionHandlesIntegerPassedInPlaceOfModel()
    {
        $egg = factory(Egg::class)->make([
            'config_startup' => '{"test": "start"}',
            'config_stop' => 'test',
            'config_files' => '{"test": "file"}',
            'config_logs' => '{"test": "logs"}',
        ]);

        $this->repository->shouldReceive('getWithCopyAttributes')->with($egg->id)->once()->andReturn($egg);

        $response = $this->service->handle($egg->id);
        $this->assertNotEmpty($response);
        $this->assertTrue(is_array($response), 'Assert response is an array.');
        $this->assertArrayHasKey('startup', $response);
        $this->assertArrayHasKey('stop', $response);
        $this->assertArrayHasKey('configs', $response);
        $this->assertArrayHasKey('log', $response);
        $this->assertArrayHasKey('query', $response);
        $this->assertEquals('start', object_get($response['startup'], 'test'));
        $this->assertEquals('test', 'test');
        $this->assertEquals('file', object_get($response['configs'], 'test'));
        $this->assertEquals('logs', object_get($response['log'], 'test'));
        $this->assertEquals('none', $response['query']);
    }
}
