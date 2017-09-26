<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
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
            $this->assertEquals(trans('exceptions.service.variables.env_not_unique', [
                'name' => 'TEST_VAR_123',
            ]), $exception->getMessage());
        }
    }

    /**
     * Test that all of the reserved variables defined in the model trigger an exception.
     *
     * @dataProvider reservedNamesProvider
     * @expectedException \Pterodactyl\Exceptions\Service\ServiceVariable\ReservedVariableNameException
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
