<?php

namespace Tests\Unit\Services\Eggs\Variables;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Services\Eggs\Variables\VariableCreationService;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;

class VariableCreationServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Eggs\Variables\VariableCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(EggVariableRepositoryInterface::class);

        $this->service = new VariableCreationService($this->repository);
    }

    /**
     * Test basic functionality, data should be stored in the database.
     */
    public function testVariableIsCreatedAndStored()
    {
        $data = ['env_variable' => 'TEST_VAR_123', 'default_value' => 'test'];
        $this->repository->shouldReceive('create')->with(m::subset([
            'egg_id' => 1,
            'default_value' => 'test',
            'user_viewable' => false,
            'user_editable' => false,
            'env_variable' => 'TEST_VAR_123',
        ]))->once()->andReturn(new EggVariable);

        $this->assertInstanceOf(EggVariable::class, $this->service->handle(1, $data));
    }

    /**
     * Test that the option key in the data array is properly parsed.
     */
    public function testOptionsPassedInArrayKeyAreParsedProperly()
    {
        $data = ['env_variable' => 'TEST_VAR_123', 'options' => ['user_viewable', 'user_editable']];
        $this->repository->shouldReceive('create')->with(m::subset([
            'default_value' => '',
            'user_viewable' => true,
            'user_editable' => true,
            'env_variable' => 'TEST_VAR_123',
        ]))->once()->andReturn(new EggVariable);

        $this->assertInstanceOf(EggVariable::class, $this->service->handle(1, $data));
    }

    /**
     * Test that an empty (null) value passed in the option key is handled
     * properly as an array. Also tests the same case aganist the default_value.
     *
     * @see https://github.com/Pterodactyl/Panel/issues/841
     * @see https://github.com/Pterodactyl/Panel/issues/943
     */
    public function testNullOptionValueIsPassedAsArray()
    {
        $data = ['env_variable' => 'TEST_VAR_123', 'options' => null, 'default_value' => null];
        $this->repository->shouldReceive('create')->with(m::subset([
            'default_value' => '',
            'user_viewable' => false,
            'user_editable' => false,
        ]))->once()->andReturn(new EggVariable);

        $this->assertInstanceOf(EggVariable::class, $this->service->handle(1, $data));
    }

    /**
     * Test that all of the reserved variables defined in the model trigger an exception.
     *
     * @dataProvider reservedNamesProvider
     * @expectedException \Pterodactyl\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function testExceptionIsThrownIfEnvironmentVariableIsInListOfReservedNames(string $variable)
    {
        $this->service->handle(1, ['env_variable' => $variable]);
    }

    /**
     * Test that the egg ID applied in the function takes higher priority than an
     * ID passed into the handler.
     */
    public function testEggIdPassedInDataIsNotApplied()
    {
        $data = ['egg_id' => 123456, 'env_variable' => 'TEST_VAR_123'];
        $this->repository->shouldReceive('create')->with(m::subset([
            'egg_id' => 1,
        ]))->once()->andReturn(new EggVariable);

        $this->assertInstanceOf(EggVariable::class, $this->service->handle(1, $data));
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
