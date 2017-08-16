<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Tests\Unit\Services\Services\Options;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Services\Services\Options\OptionUpdateService;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Exceptions\Services\ServiceOption\NoParentConfigurationFoundException;

class OptionUpdateServiceTest extends TestCase
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
     * @var \Pterodactyl\Services\Services\Options\OptionUpdateService
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
            $this->assertEquals(trans('admin/exceptions.service.options.must_be_child'), $exception->getMessage());
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
