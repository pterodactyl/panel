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
use Ramsey\Uuid\Uuid;
use Pterodactyl\Models\ServiceOption;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Services\Services\Options\OptionCreationService;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceOption\NoParentConfigurationFoundException;

class OptionCreationServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\Options\OptionCreationService
     */
    protected $service;

    /**
     * @var \Ramsey\Uuid\Uuid|\Mockery\Mock
     */
    protected $uuid;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->repository = m::mock(ServiceOptionRepositoryInterface::class);
        $this->uuid = m::mock('overload:' . Uuid::class);

        $this->service = new OptionCreationService($this->config, $this->repository);
    }

    /**
     * Test that a new model is created when not using the config from attribute.
     */
    public function testCreateNewModelWithoutUsingConfigFrom()
    {
        $model = factory(ServiceOption::class)->make();

        $this->config->shouldReceive('get')->with('pterodactyl.service.author')->once()->andReturn('test@example.com');
        $this->uuid->shouldReceive('uuid4->toString')->withNoArgs()->once()->andReturn('uuid-string');
        $this->repository->shouldReceive('create')->with([
            'name' => $model->name,
            'config_from' => null,
            'tag' => 'test@example.com:' . $model->tag,
            'uuid' => 'uuid-string',
        ], true, true)->once()->andReturn($model);

        $response = $this->service->handle(['name' => $model->name, 'tag' => $model->tag]);

        $this->assertNotEmpty($response);
        $this->assertNull(object_get($response, 'config_from'));
        $this->assertEquals($model->name, $response->name);
    }

    /**
     * Test that passing a bad tag into the function will set the correct tag.
     */
    public function testCreateNewModelUsingLongTagForm()
    {
        $model = factory(ServiceOption::class)->make([
            'tag' => 'test@example.com:tag',
        ]);

        $this->config->shouldReceive('get')->with('pterodactyl.service.author')->once()->andReturn('test@example.com');
        $this->uuid->shouldReceive('uuid4->toString')->withNoArgs()->once()->andReturn('uuid-string');
        $this->repository->shouldReceive('create')->with([
            'name' => $model->name,
            'config_from' => null,
            'tag' => $model->tag,
            'uuid' => 'uuid-string',
        ], true, true)->once()->andReturn($model);

        $response = $this->service->handle(['name' => $model->name, 'tag' => 'bad@example.com:tag']);

        $this->assertNotEmpty($response);
        $this->assertNull(object_get($response, 'config_from'));
        $this->assertEquals($model->name, $response->name);
        $this->assertEquals('test@example.com:tag', $response->tag);
    }

    /**
     * Test that a new model is created when using the config from attribute.
     */
    public function testCreateNewModelUsingConfigFrom()
    {
        $model = factory(ServiceOption::class)->make();

        $data = [
            'name' => $model->name,
            'service_id' => $model->service_id,
            'tag' => 'test@example.com:tag',
            'config_from' => 1,
            'uuid' => 'uuid-string',
        ];

        $this->repository->shouldReceive('findCountWhere')->with([
            ['service_id', '=', $data['service_id']],
            ['id', '=', $data['config_from']],
        ])->once()->andReturn(1);

        $this->config->shouldReceive('get')->with('pterodactyl.service.author')->once()->andReturn('test@example.com');
        $this->uuid->shouldReceive('uuid4->toString')->withNoArgs()->once()->andReturn('uuid-string');
        $this->repository->shouldReceive('create')->with($data, true, true)->once()->andReturn($model);

        $response = $this->service->handle($data);

        $this->assertNotEmpty($response);
        $this->assertEquals($response, $model);
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
