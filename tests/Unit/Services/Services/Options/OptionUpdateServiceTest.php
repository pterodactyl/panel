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
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Services\Services\Options\OptionUpdateService;
use Pterodactyl\Exceptions\Service\ServiceOption\NoParentConfigurationFoundException;

class OptionUpdateServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Models\Egg
     */
    protected $model;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\Options\OptionUpdateService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->model = factory(Egg::class)->make();
        $this->repository = m::mock(EggRepositoryInterface::class);

        $this->service = new OptionUpdateService($this->repository);
    }

    /**
     * Test that an option is updated when no config_from attribute is passed.
     */
    public function testOptionIsUpdatedWhenNoConfigFromIsProvided()
    {
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, ['test_field' => 'field_value'])->once()->andReturnNull();

        $this->service->handle($this->model, ['test_field' => 'field_value']);
    }

    /**
     * Test that option is updated when a valid config_from attribute is passed.
     */
    public function testOptionIsUpdatedWhenValidConfigFromIsPassed()
    {
        $this->repository->shouldReceive('findCountWhere')->with([
            ['service_id', '=', $this->model->service_id],
            ['id', '=', 1],
        ])->once()->andReturn(1);

        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, ['config_from' => 1])->once()->andReturnNull();

        $this->service->handle($this->model, ['config_from' => 1]);
    }

    /**
     * Test that an exception is thrown if an invalid config_from attribute is passed.
     */
    public function testExceptionIsThrownIfInvalidParentConfigIsPassed()
    {
        $this->repository->shouldReceive('findCountWhere')->with([
            ['service_id', '=', $this->model->service_id],
            ['id', '=', 1],
        ])->once()->andReturn(0);

        try {
            $this->service->handle($this->model, ['config_from' => 1]);
        } catch (Exception $exception) {
            $this->assertInstanceOf(NoParentConfigurationFoundException::class, $exception);
            $this->assertEquals(trans('exceptions.service.options.must_be_child'), $exception->getMessage());
        }
    }

    /**
     * Test that an integer linking to a model can be passed in place of the ServiceOption model.
     */
    public function testIntegerCanBePassedInPlaceOfModel()
    {
        $this->repository->shouldReceive('find')->with($this->model->id)->once()->andReturn($this->model);
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, ['test_field' => 'field_value'])->once()->andReturnNull();

        $this->service->handle($this->model->id, ['test_field' => 'field_value']);
    }
}
