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
use Pterodactyl\Services\Services\Options\InstallScriptUpdateService;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Exceptions\Service\ServiceOption\InvalidCopyFromException;

class InstallScriptUpdateServiceTest extends TestCase
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
     * @var \Pterodactyl\Models\ServiceOption
     */
    protected $model;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\Options\InstallScriptUpdateService
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

        $this->service = new InstallScriptUpdateService($this->repository);
    }

    /**
     * Test that passing a new copy_script_from attribute works properly.
     */
    public function testUpdateWithValidCopyScriptFromAttribute()
    {
        $this->data['copy_script_from'] = 1;

        $this->repository->shouldReceive('isCopiableScript')->with(1, $this->model->service_id)->once()->andReturn(true);
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, $this->data)->andReturnNull();

        $this->service->handle($this->model, $this->data);
    }

    /**
     * Test that an exception gets raised when the script is not copiable.
     */
    public function testUpdateWithInvalidCopyScriptFromAttribute()
    {
        $this->data['copy_script_from'] = 1;

        $this->repository->shouldReceive('isCopiableScript')->with(1, $this->model->service_id)->once()->andReturn(false);
        try {
            $this->service->handle($this->model, $this->data);
        } catch (Exception $exception) {
            $this->assertInstanceOf(InvalidCopyFromException::class, $exception);
            $this->assertEquals(trans('admin/exceptions.service.options.invalid_copy_id'), $exception->getMessage());
        }
    }

    /**
     * Test standard functionality.
     */
    public function testUpdateWithoutNewCopyScriptFromAttribute()
    {
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, $this->data)->andReturnNull();

        $this->service->handle($this->model, $this->data);
    }

    /**
     * Test that an integer can be passed in place of a model.
     */
    public function testFunctionAcceptsIntegerInPlaceOfModel()
    {
        $this->repository->shouldReceive('find')->with($this->model->id)->once()->andReturn($this->model);
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, $this->data)->andReturnNull();

        $this->service->handle($this->model->id, $this->data);
    }
}
