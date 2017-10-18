<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Services\Options;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Egg;
use Tests\Traits\MocksUuids;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Services\Eggs\EggCreationService;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\NoParentConfigurationFoundException;

class EggCreationServiceTest extends TestCase
{
    use MocksUuids;

    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Eggs\EggCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->repository = m::mock(EggRepositoryInterface::class);

        $this->service = new EggCreationService($this->config, $this->repository);
    }

    /**
     * Test that a new model is created when not using the config from attribute.
     */
    public function testCreateNewModelWithoutUsingConfigFrom()
    {
        $model = factory(Egg::class)->make();

        $this->config->shouldReceive('get')->with('pterodactyl.service.author')->once()->andReturn('test@example.com');
        $this->repository->shouldReceive('create')->with([
            'uuid' => $this->getKnownUuid(),
            'author' => 'test@example.com',
            'config_from' => null,
            'name' => $model->name,
        ], true, true)->once()->andReturn($model);

        $response = $this->service->handle(['name' => $model->name]);

        $this->assertNotEmpty($response);
        $this->assertNull(object_get($response, 'config_from'));
        $this->assertEquals($model->name, $response->name);
    }

    /**
     * Test that a new model is created when using the config from attribute.
     */
    public function testCreateNewModelUsingConfigFrom()
    {
        $model = factory(Egg::class)->make();

        $this->repository->shouldReceive('findCountWhere')->with([
            ['nest_id', '=', $model->nest_id],
            ['id', '=', 12345],
        ])->once()->andReturn(1);

        $this->config->shouldReceive('get')->with('pterodactyl.service.author')->once()->andReturn('test@example.com');
        $this->repository->shouldReceive('create')->with([
            'nest_id' => $model->nest_id,
            'config_from' => 12345,
            'uuid' => $this->getKnownUuid(),
            'author' => 'test@example.com',
        ], true, true)->once()->andReturn($model);

        $response = $this->service->handle([
            'nest_id' => $model->nest_id,
            'config_from' => 12345,
        ]);

        $this->assertNotEmpty($response);
        $this->assertEquals($response, $model);
    }

    /**
     * Test that certain data, such as the UUID or author takes priority over data
     * that is passed into the function.
     */
    public function testDataProvidedByHandlerTakesPriorityOverPassedData()
    {
        $model = factory(Egg::class)->make();

        $this->config->shouldReceive('get')->with('pterodactyl.service.author')->once()->andReturn('test@example.com');
        $this->repository->shouldReceive('create')->with([
            'uuid' => $this->getKnownUuid(),
            'author' => 'test@example.com',
            'config_from' => null,
            'name' => $model->name,
        ], true, true)->once()->andReturn($model);

        $response = $this->service->handle(['name' => $model->name, 'uuid' => 'should-be-ignored', 'author' => 'should-be-ignored']);

        $this->assertNotEmpty($response);
        $this->assertNull(object_get($response, 'config_from'));
        $this->assertEquals($model->name, $response->name);
    }

    /**
     * Test that an exception is thrown if no parent configuration can be located.
     */
    public function testExceptionIsThrownIfNoParentConfigurationIsFound()
    {
        $this->repository->shouldReceive('findCountWhere')->with([
            ['nest_id', '=', null],
            ['id', '=', 1],
        ])->once()->andReturn(0);

        try {
            $this->service->handle(['config_from' => 1]);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(NoParentConfigurationFoundException::class, $exception);
            $this->assertEquals(trans('exceptions.nest.egg.must_be_child'), $exception->getMessage());
        }
    }
}
