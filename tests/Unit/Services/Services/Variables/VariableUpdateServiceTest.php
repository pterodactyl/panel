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

namespace Tests\Unit\Services\Services\Variables;

use Exception;
use Mockery as m;
use Tests\TestCase;
use PhpParser\Node\Expr\Variable;
use Pterodactyl\Models\ServiceVariable;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Services\Variables\VariableUpdateService;
use Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface;

class VariableUpdateServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Models\ServiceVariable
     */
    protected $model;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Services\Variables\VariableUpdateService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->model = factory(ServiceVariable::class)->make();
        $this->repository = m::mock(ServiceVariableRepositoryInterface::class);

        $this->service = new VariableUpdateService($this->repository);
    }

    /**
     * Test the function when no env_variable key is passed into the function.
     */
    public function testVariableIsUpdatedWhenNoEnvironmentVariableIsPassed()
    {
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, [
                'user_viewable' => false,
                'user_editable' => false,
                'test-data' => 'test-value',
            ])->once()->andReturn(true);

        $this->assertTrue($this->service->handle($this->model, ['test-data' => 'test-value']));
    }

    /**
     * Test the function when a valid env_variable key is passed into the function.
     */
    public function testVariableIsUpdatedWhenValidEnvironmentVariableIsPassed()
    {
        $this->repository->shouldReceive('withColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([
                ['env_variable', '=', 'TEST_VAR_123'],
                ['option_id', '=', $this->model->option_id],
                ['id', '!=', $this->model->id],
            ])->once()->andReturn(0);

        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, [
                'user_viewable' => false,
                'user_editable' => false,
                'env_variable' => 'TEST_VAR_123',
            ])->once()->andReturn(true);

        $this->assertTrue($this->service->handle($this->model, ['env_variable' => 'TEST_VAR_123']));
    }

    /**
     * Test that a non-unique environment variable triggers an exception.
     */
    public function testExceptionIsThrownIfEnvironmentVariableIsNotUnique()
    {
        $this->repository->shouldReceive('withColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([
                ['env_variable', '=', 'TEST_VAR_123'],
                ['option_id', '=', $this->model->option_id],
                ['id', '!=', $this->model->id],
            ])->once()->andReturn(1);

        try {
            $this->service->handle($this->model, ['env_variable' => 'TEST_VAR_123']);
        } catch (Exception $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(trans('admin/exceptions.service.variables.env_not_unique', [
                'name' => 'TEST_VAR_123',
            ]), $exception->getMessage());
        }
    }

    /**
     * Test that all of the reserved variables defined in the model trigger an exception.
     *
     * @dataProvider reservedNamesProvider
     * @expectedException \Pterodactyl\Exceptions\Services\ServiceVariable\ReservedVariableNameException
     */
    public function testExceptionIsThrownIfEnvironmentVariableIsInListOfReservedNames($variable)
    {
        $this->service->handle($this->model, ['env_variable' => $variable]);
    }

    /**
     * Provides the data to be used in the tests.
     *
     * @return array
     */
    public function reservedNamesProvider()
    {
        $data = [];
        $exploded = explode(',', ServiceVariable::RESERVED_ENV_NAMES);
        foreach ($exploded as $e) {
            $data[] = [$e];
        }

        return $data;
    }
}
