<?php

namespace Tests\Unit\Services\Eggs\Variables;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Eggs\Variables\VariableUpdateService;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;

class VariableUpdateServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Models\EggVariable|\Mockery\Mock
     */
    protected $model;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Eggs\Variables\VariableUpdateService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->model = factory(EggVariable::class)->make();
        $this->repository = m::mock(EggVariableRepositoryInterface::class);

        $this->service = new VariableUpdateService($this->repository);
    }

    /**
     * Test the function when no env_variable key is passed into the function.
     */
    public function testVariableIsUpdatedWhenNoEnvironmentVariableIsPassed()
    {
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, m::subset([
                'user_viewable' => false,
                'user_editable' => false,
            ]))->once()->andReturn(true);

        $this->assertTrue($this->service->handle($this->model, []));
    }

    /**
     * Test that a null value passed in for the default is converted to a string.
     *
     * @see https://github.com/Pterodactyl/Panel/issues/934
     */
    public function testNullDefaultValue()
    {
        $this->repository->shouldReceive('withoutFreshModel->update')->with($this->model->id, m::subset([
            'user_viewable' => false,
            'user_editable' => false,
            'default_value' => '',
        ]))->once()->andReturn(true);

        $this->assertTrue($this->service->handle($this->model, ['default_value' => null]));
    }

    /**
     * Test the function when a valid env_variable key is passed into the function.
     */
    public function testVariableIsUpdatedWhenValidEnvironmentVariableIsPassed()
    {
        $this->repository->shouldReceive('setColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([
                ['env_variable', '=', 'TEST_VAR_123'],
                ['egg_id', '=', $this->model->option_id],
                ['id', '!=', $this->model->id],
            ])->once()->andReturn(0);

        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, m::subset([
                'user_viewable' => false,
                'user_editable' => false,
                'env_variable' => 'TEST_VAR_123',
            ]))->once()->andReturn(true);

        $this->assertTrue($this->service->handle($this->model, ['env_variable' => 'TEST_VAR_123']));
    }

    /**
     * Test that an empty (null) value passed in the option key is handled
     * properly as an array. Also tests that a null description is handled.
     *
     * @see https://github.com/Pterodactyl/Panel/issues/841
     */
    public function testNullOptionValueIsPassedAsArray()
    {
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, m::subset([
                'user_viewable' => false,
                'user_editable' => false,
                'description' => '',
            ]))->once()->andReturn(true);

        $this->assertTrue($this->service->handle($this->model, ['options' => null, 'description' => null]));
    }

    /**
     * Test that data passed into the handler is overwritten inside the handler.
     */
    public function testDataPassedIntoHandlerTakesLowerPriorityThanDataSet()
    {
        $this->repository->shouldReceive('setColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([
                ['env_variable', '=', 'TEST_VAR_123'],
                ['egg_id', '=', $this->model->option_id],
                ['id', '!=', $this->model->id],
            ])->once()->andReturn(0);

        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->model->id, m::subset([
                'user_viewable' => false,
                'user_editable' => false,
                'env_variable' => 'TEST_VAR_123',
            ]))->once()->andReturn(true);

        $this->assertTrue($this->service->handle($this->model, ['user_viewable' => 123456, 'env_variable' => 'TEST_VAR_123']));
    }

    /**
     * Test that a non-unique environment variable triggers an exception.
     */
    public function testExceptionIsThrownIfEnvironmentVariableIsNotUnique()
    {
        $this->repository->shouldReceive('setColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([
                ['env_variable', '=', 'TEST_VAR_123'],
                ['egg_id', '=', $this->model->option_id],
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
     * @expectedException \Pterodactyl\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function testExceptionIsThrownIfEnvironmentVariableIsInListOfReservedNames(string $variable)
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
        $exploded = explode(',', EggVariable::RESERVED_ENV_NAMES);
        foreach ($exploded as $e) {
            $data[] = [$e];
        }

        return $data;
    }
}
