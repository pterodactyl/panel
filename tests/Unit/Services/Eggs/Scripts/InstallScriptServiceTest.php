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
use Pterodactyl\Services\Eggs\Scripts\InstallScriptService;
use Pterodactyl\Contracts\Repository\EggRepositoryInterface;
use Pterodactyl\Exceptions\Service\Egg\InvalidCopyFromException;

class InstallScriptServiceTest extends TestCase
{
    /**
     * @var array
     */
    protected $data = [
        'script_install' => 'test-script',
        'script_is_privileged' => true,
        'script_entry' => '/bin/bash',
        'script_container' => 'ubuntu',
        'copy_script_from' => null,
    ];

    /**
     * @var \Pterodactyl\Models\Egg
     */
    protected $model;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Eggs\Scripts\InstallScriptService
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

        $this->service = new InstallScriptService($this->repository);
    }

    /**
     * Test that passing a new copy_script_from attribute works properly.
     */
    public function testUpdateWithValidCopyScriptFromAttribute()
    {
        $this->data['copy_script_from'] = 1;

        $this->repository->shouldReceive('isCopiableScript')->with(1, $this->model->nest_id)->once()->andReturn(true);
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, $this->data)->andReturnNull();

        $this->service->handle($this->model, $this->data);
    }

    /**
     * Test that an exception gets raised when the script is not copiable.
     */
    public function testUpdateWithInvalidCopyScriptFromAttribute()
    {
        $this->data['copy_script_from'] = 1;

        $this->repository->shouldReceive('isCopiableScript')->with(1, $this->model->nest_id)->once()->andReturn(false);
        try {
            $this->service->handle($this->model, $this->data);
        } catch (Exception $exception) {
            $this->assertInstanceOf(InvalidCopyFromException::class, $exception);
            $this->assertEquals(trans('exceptions.nest.egg.invalid_copy_id'), $exception->getMessage());
        }
    }

    /**
     * Test standard functionality.
     */
    public function testUpdateWithoutNewCopyScriptFromAttribute()
    {
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, $this->data)->andReturnNull();

        $this->service->handle($this->model, $this->data);
    }

    /**
     * Test that an integer can be passed in place of a model.
     */
    public function testFunctionAcceptsIntegerInPlaceOfModel()
    {
        $this->repository->shouldReceive('find')->with($this->model->id)->once()->andReturn($this->model);
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, $this->data)->andReturnNull();

        $this->service->handle($this->model->id, $this->data);
    }
}
