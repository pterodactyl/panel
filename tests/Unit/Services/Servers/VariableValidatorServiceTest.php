<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\EggVariable;
use Illuminate\Contracts\Validation\Factory;
use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;

class VariableValidatorServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface
     */
    protected $optionVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface
     */
    protected $serverVariableRepository;

    /**
     * @var \Pterodactyl\Services\Servers\VariableValidatorService
     */
    protected $service;

    /**
     * @var \Illuminate\Validation\Factory
     */
    protected $validator;

    /**
     * @var \Illuminate\Support\Collection
     */
    protected $variables;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->variables = collect(
            [
                factory(EggVariable::class)->states('editable', 'viewable')->make(),
                factory(EggVariable::class)->states('viewable')->make(),
                factory(EggVariable::class)->states('editable')->make(),
                factory(EggVariable::class)->make(),
            ]
        );

        $this->optionVariableRepository = m::mock(EggVariableRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->serverVariableRepository = m::mock(ServerVariableRepositoryInterface::class);
        $this->validator = m::mock(Factory::class);

        $this->service = new VariableValidatorService(
            $this->optionVariableRepository,
            $this->serverRepository,
            $this->serverVariableRepository,
            $this->validator
        );
    }

    /**
     * Test that setting fields returns an instance of the class.
     */
    public function testSettingFieldsShouldReturnInstanceOfSelf()
    {
        $response = $this->service->setFields([]);

        $this->assertInstanceOf(VariableValidatorService::class, $response);
    }

    /**
     * Test that setting administrator value returns an instance of the class.
     */
    public function testSettingAdminShouldReturnInstanceOfSelf()
    {
        $response = $this->service->isAdmin();

        $this->assertInstanceOf(VariableValidatorService::class, $response);
    }

    /**
     * Test that getting the results returns an array of values.
     */
    public function testGettingResultsReturnsAnArrayOfValues()
    {
        $response = $this->service->getResults();

        $this->assertTrue(is_array($response));
    }

    /**
     * Test that when no variables are found for an option no data is returned.
     */
    public function testEmptyResultSetShouldBeReturnedIfNoVariablesAreFound()
    {
        $this->optionVariableRepository->shouldReceive('findWhere')->with([['option_id', '=', 1]])->andReturn([]);

        $response = $this->service->validate(1);

        $this->assertInstanceOf(VariableValidatorService::class, $response);
        $this->assertTrue(is_array($response->getResults()));
        $this->assertEmpty($response->getResults());
    }

    /**
     * Test that variables set as user_editable=0 and/or user_viewable=0 are skipped when admin flag is not set.
     */
    public function testValidatorShouldNotProcessVariablesSetAsNotUserEditableWhenAdminFlagIsNotPassed()
    {
        $this->optionVariableRepository->shouldReceive('findWhere')->with([['option_id', '=', 1]])->andReturn($this->variables);

        $this->validator->shouldReceive('make')->with([
            'variable_value' => 'Test_SomeValue_0',
        ], [
            'variable_value' => $this->variables[0]->rules,
        ])->once()->andReturnSelf()
            ->shouldReceive('fails')->withNoArgs()->once()->andReturn(false);

        $response = $this->service->setFields([
            $this->variables[0]->env_variable => 'Test_SomeValue_0',
            $this->variables[1]->env_variable => 'Test_SomeValue_1',
            $this->variables[2]->env_variable => 'Test_SomeValue_2',
            $this->variables[3]->env_variable => 'Test_SomeValue_3',
        ])->validate(1)->getResults();

        $this->assertEquals(1, count($response), 'Assert response has a single item in array.');
        $this->assertArrayHasKey('0', $response);
        $this->assertArrayHasKey('id', $response[0]);
        $this->assertArrayHasKey('key', $response[0]);
        $this->assertArrayHasKey('value', $response[0]);

        $this->assertEquals($this->variables[0]->id, $response[0]['id']);
        $this->assertEquals($this->variables[0]->env_variable, $response[0]['key']);
        $this->assertEquals('Test_SomeValue_0', $response[0]['value']);
    }

    /**
     * Test that all variables are processed correctly if admin flag is set.
     */
    public function testValidatorShouldProcessAllVariablesWhenAdminFlagIsSet()
    {
        $this->optionVariableRepository->shouldReceive('findWhere')->with([['option_id', '=', 1]])->andReturn($this->variables);

        foreach ($this->variables as $key => $variable) {
            $this->validator->shouldReceive('make')->with([
                'variable_value' => 'Test_SomeValue_' . $key,
            ], [
                'variable_value' => $this->variables[$key]->rules,
            ])->andReturnSelf()
                ->shouldReceive('fails')->withNoArgs()->once()->andReturn(false);
        }

        $response = $this->service->isAdmin()->setFields([
            $this->variables[0]->env_variable => 'Test_SomeValue_0',
            $this->variables[1]->env_variable => 'Test_SomeValue_1',
            $this->variables[2]->env_variable => 'Test_SomeValue_2',
            $this->variables[3]->env_variable => 'Test_SomeValue_3',
        ])->validate(1)->getResults();

        $this->assertEquals(4, count($response), 'Assert response has all four items in array.');

        foreach ($response as $key => $values) {
            $this->assertArrayHasKey($key, $response);
            $this->assertArrayHasKey('id', $response[$key]);
            $this->assertArrayHasKey('key', $response[$key]);
            $this->assertArrayHasKey('value', $response[$key]);

            $this->assertEquals($this->variables[$key]->id, $response[$key]['id']);
            $this->assertEquals($this->variables[$key]->env_variable, $response[$key]['key']);
            $this->assertEquals('Test_SomeValue_' . $key, $response[$key]['value']);
        }
    }

    /**
     * Test that a DisplayValidationError is thrown when a variable is not validated.
     */
    public function testValidatorShouldThrowExceptionWhenAValidationErrorIsEncountered()
    {
        $this->optionVariableRepository->shouldReceive('findWhere')->with([['option_id', '=', 1]])->andReturn($this->variables);

        $this->validator->shouldReceive('make')->with([
            'variable_value' => null,
        ], [
            'variable_value' => $this->variables[0]->rules,
        ])->once()->andReturnSelf()
            ->shouldReceive('fails')->withNoArgs()->once()->andReturn(true);

        $this->validator->shouldReceive('errors')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('toArray')->withNoArgs()->once()->andReturn([]);

        try {
            $this->service->setFields([
                $this->variables[0]->env_variable => null,
            ])->validate(1);
        } catch (DisplayValidationException $exception) {
            $decoded = json_decode($exception->getMessage());

            $this->assertEquals(0, json_last_error(), 'Assert that response is decodable JSON.');
            $this->assertObjectHasAttribute('notice', $decoded);
            $this->assertEquals(
                trans('admin/server.exceptions.bad_variable', ['name' => $this->variables[0]->name]),
                $decoded->notice[0]
            );
        }
    }
}
