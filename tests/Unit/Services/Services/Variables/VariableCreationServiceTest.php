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
