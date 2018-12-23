<?php

namespace Tests\Unit\Services\Eggs\Variables;

use Exception;
use Mockery as m;
use Tests\TestCase;
use BadMethodCallException;
use Pterodactyl\Models\EggVariable;
use Illuminate\Contracts\Validation\Factory;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Eggs\Variables\VariableUpdateService;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;

class VariableUpdateServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Models\EggVariable|\Mockery\Mock
     */
    private $model;

    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \Illuminate\Contracts\Validation\Factory|\Mockery\Mock
     */
    private $validator;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->model = factory(EggVariable::class)->make();
        $this->repository = m::mock(EggVariableRepositoryInterface::class);
        $this->validator = m::mock(Factory::class);
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

        $this->assertTrue($this->getService()->handle($this->model, []));
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

        $this->assertTrue($this->getService()->handle($this->model, ['default_value' => null]));
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

        $this->assertTrue($this->getService()->handle($this->model, ['env_variable' => 'TEST_VAR_123']));
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

        $this->assertTrue($this->getService()->handle($this->model, ['options' => null, 'description' => null]));
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

        $this->assertTrue($this->getService()->handle($this->model, ['user_viewable' => 123456, 'env_variable' => 'TEST_VAR_123']));
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
            $this->getService()->handle($this->model, ['env_variable' => 'TEST_VAR_123']);
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
        $this->getService()->handle($this->model, ['env_variable' => $variable]);
    }

    /**
     * Test that validation errors due to invalid rules are caught and handled properly.
     *
     * @expectedException \Pterodactyl\Exceptions\Service\Egg\Variable\BadValidationRuleException
     * @expectedExceptionMessage The validation rule "hodor_door" is not a valid rule for this application.
     */
    public function testInvalidValidationRulesResultInException()
    {
        $data = ['env_variable' => 'TEST_VAR_123', 'rules' => 'string|hodorDoor'];

        $this->repository->shouldReceive('setColumns->findCountWhere')->once()->andReturn(0);

        $this->validator->shouldReceive('make')->once()
            ->with(['__TEST' => 'test'], ['__TEST' => 'string|hodorDoor'])
            ->andReturnSelf();

        $this->validator->shouldReceive('fails')->once()
            ->withNoArgs()
            ->andThrow(new BadMethodCallException('Method [validateHodorDoor] does not exist.'));

        $this->getService()->handle($this->model, $data);
    }

    /**
     * Test that an exception not stemming from a bad rule is not caught.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Received something, but no expectations were specified.
     */
    public function testExceptionNotCausedByBadRuleIsNotCaught()
    {
        $data = ['rules' => 'string'];

        $this->validator->shouldReceive('make')->once()
            ->with(['__TEST' => 'test'], ['__TEST' => 'string'])
            ->andReturnSelf();

        $this->validator->shouldReceive('fails')->once()
            ->withNoArgs()
            ->andThrow(new BadMethodCallException('Received something, but no expectations were specified.'));

        $this->getService()->handle($this->model, $data);
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

    /**
     * Return an instance of the service with mocked dependencies for testing.
     *
     * @return \Pterodactyl\Services\Eggs\Variables\VariableUpdateService
     */
    private function getService(): VariableUpdateService
    {
        return new VariableUpdateService($this->repository, $this->validator);
    }
}
