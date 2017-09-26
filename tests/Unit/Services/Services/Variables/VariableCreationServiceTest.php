<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Services\Variables;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\ServiceOption;
use Pterodactyl\Models\ServiceVariable;
use Pterodactyl\Services\Services\Variables\VariableCreationService;
use Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface;

class VariableCreationServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceOptionRepositoryInterface
     */
    protected $serviceOptionRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServiceVariableRepositoryInterface
     */
    protected $serviceVariableRepository;

    /**
     * @var \Pterodactyl\Services\Services\Variables\VariableCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->serviceOptionRepository = m::mock(ServiceOptionRepositoryInterface::class);
        $this->serviceVariableRepository = m::mock(ServiceVariableRepositoryInterface::class);

        $this->service = new VariableCreationService($this->serviceOptionRepository, $this->serviceVariableRepository);
    }

    /**
     * Test basic functionality, data should be stored in the database.
     */
    public function testVariableIsCreatedAndStored()
    {
        $data = ['env_variable' => 'TEST_VAR_123'];
        $this->serviceVariableRepository->shouldReceive('create')->with([
           'option_id' => 1,
           'user_viewable' => false,
           'user_editable' => false,
           'env_variable' => 'TEST_VAR_123',
        ])->once()->andReturn(new ServiceVariable);

        $this->assertInstanceOf(ServiceVariable::class, $this->service->handle(1, $data));
    }

    /**
     * Test that the option key in the data array is properly parsed.
     */
    public function testOptionsPassedInArrayKeyAreParsedProperly()
    {
        $data = ['env_variable' => 'TEST_VAR_123', 'options' => ['user_viewable', 'user_editable']];
        $this->serviceVariableRepository->shouldReceive('create')->with([
            'option_id' => 1,
            'user_viewable' => true,
            'user_editable' => true,
            'env_variable' => 'TEST_VAR_123',
            'options' => ['user_viewable', 'user_editable'],
        ])->once()->andReturn(new ServiceVariable);

        $this->assertInstanceOf(ServiceVariable::class, $this->service->handle(1, $data));
    }

    /**
     * Test that all of the reserved variables defined in the model trigger an exception.
     *
     * @dataProvider reservedNamesProvider
     * @expectedException \Pterodactyl\Exceptions\Service\ServiceVariable\ReservedVariableNameException
     */
    public function testExceptionIsThrownIfEnvironmentVariableIsInListOfReservedNames($variable)
    {
        $this->service->handle(1, ['env_variable' => $variable]);
    }

    /**
     * Test that a model can be passed in place of an integer.
     */
    public function testModelCanBePassedInPlaceOfInteger()
    {
        $model = factory(ServiceOption::class)->make();
        $data = ['env_variable' => 'TEST_VAR_123'];

        $this->serviceVariableRepository->shouldReceive('create')->with([
            'option_id' => $model->id,
            'user_viewable' => false,
            'user_editable' => false,
            'env_variable' => 'TEST_VAR_123',
        ])->once()->andReturn(new ServiceVariable);

        $this->assertInstanceOf(ServiceVariable::class, $this->service->handle($model, $data));
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
