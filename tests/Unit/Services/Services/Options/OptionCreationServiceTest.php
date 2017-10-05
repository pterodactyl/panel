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
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Services\Services\Options\OptionCreationService;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceOption\NoParentConfigurationFoundException;

class OptionCreationServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Models\ServiceOption
     */
    protected $model;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\Options\OptionCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->model = factory(ServiceOption::class)->make();
        $this->repository = m::mock(ServiceOptionRepositoryInterface::class);

        $this->service = new OptionCreationService($this->repository);
    }

    /**
     * Test that a new model is created when not using the config from attribute.
     */
    public function testCreateNewModelWithoutUsingConfigFrom()
    {
        $this->repository->shouldReceive('create')->with(['name' => $this->model->name, 'config_from' => null])
            ->once()->andReturn($this->model);

        $response = $this->service->handle(['name' => $this->model->name]);

        $this->assertNotEmpty($response);
        $this->assertNull(object_get($response, 'config_from'));
        $this->assertEquals($this->model->name, $response->name);
    }

    /**
     * Test that a new model is created when using the config from attribute.
     */
    public function testCreateNewModelUsingConfigFrom()
    {
        $data = [
            'name' => $this->model->name,
            'service_id' => $this->model->service_id,
            'config_from' => 1,
        ];

        $this->repository->shouldReceive('findCountWhere')->with([
            ['service_id', '=', $data['service_id']],
            ['id', '=', $data['config_from']],
        ])->once()->andReturn(1);

        $this->repository->shouldReceive('create')->with($data)
            ->once()->andReturn($this->model);

        $response = $this->service->handle($data);

        $this->assertNotEmpty($response);
        $this->assertEquals($response, $this->model);
    }

    /**
     * Test that an exception is thrown if no parent configuration can be located.
     */
    public function testExceptionIsThrownIfNoParentConfigurationIsFound()
    {
        $this->repository->shouldReceive('findCountWhere')->with([
            ['service_id', '=', null],
            ['id', '=', 1],
        ])->once()->andReturn(0);

        try {
            $this->service->handle(['config_from' => 1]);
        } catch (Exception $exception) {
            $this->assertInstanceOf(NoParentConfigurationFoundException::class, $exception);
            $this->assertEquals(trans('exceptions.service.options.must_be_child'), $exception->getMessage());
        }
    }
}
