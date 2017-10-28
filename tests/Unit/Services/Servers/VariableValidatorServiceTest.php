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
use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use Pterodactyl\Models\EggVariable;
use Illuminate\Contracts\Validation\Factory;
use Pterodactyl\Exceptions\PterodactylException;
use Pterodactyl\Exceptions\DisplayValidationException;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;

class VariableValidatorServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface|\Mockery\Mock
     */
    protected $optionVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    protected $serverRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface|\Mockery\Mock
     */
    protected $serverVariableRepository;

    /**
     * @var \Illuminate\Contracts\Validation\Factory|\Mockery\Mock
     */
    protected $validator;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->optionVariableRepository = m::mock(EggVariableRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->serverVariableRepository = m::mock(ServerVariableRepositoryInterface::class);
        $this->validator = m::mock(Factory::class);
    }

    /**
     * Test that when no variables are found for an option no data is returned.
     */
    public function testEmptyResultSetShouldBeReturnedIfNoVariablesAreFound()
    {
        $this->optionVariableRepository->shouldReceive('findWhere')->with([['egg_id', '=', 1]])->andReturn(collect([]));

        $response = $this->getService()->handle(1, []);
        $this->assertEmpty($response);
        $this->assertInstanceOf(Collection::class, $response);
    }

    /**
     * Test that variables set as user_editable=0 and/or user_viewable=0 are skipped when admin flag is not set.
     */
    public function testValidatorShouldNotProcessVariablesSetAsNotUserEditableWhenAdminFlagIsNotPassed()
    {
        $variables = $this->getVariableCollection();
        $this->optionVariableRepository->shouldReceive('findWhere')->with([['egg_id', '=', 1]])->andReturn($variables);

        $this->validator->shouldReceive('make')->with([
            'variable_value' => 'Test_SomeValue_0',
        ], [
            'variable_value' => $variables[0]->rules,
        ])->once()->andReturnSelf();
        $this->validator->shouldReceive('fails')->withNoArgs()->once()->andReturn(false);

        $response = $this->getService()->handle(1, [
            $variables[0]->env_variable => 'Test_SomeValue_0',
            $variables[1]->env_variable => 'Test_SomeValue_1',
            $variables[2]->env_variable => 'Test_SomeValue_2',
            $variables[3]->env_variable => 'Test_SomeValue_3',
        ]);

        $this->assertNotEmpty($response);
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertEquals(1, $response->count(), 'Assert response has a single item in collection.');

        $variable = $response->first();
        $this->assertObjectHasAttribute('id', $variable);
        $this->assertObjectHasAttribute('key', $variable);
        $this->assertObjectHasAttribute('value', $variable);
        $this->assertSame($variables[0]->id, $variable->id);
        $this->assertSame($variables[0]->env_variable, $variable->key);
        $this->assertSame('Test_SomeValue_0', $variable->value);
    }

    /**
     * Test that all variables are processed correctly if admin flag is set.
     */
    public function testValidatorShouldProcessAllVariablesWhenAdminFlagIsSet()
    {
        $variables = $this->getVariableCollection();
        $this->optionVariableRepository->shouldReceive('findWhere')->with([['egg_id', '=', 1]])->andReturn($variables);

        foreach ($variables as $key => $variable) {
            $this->validator->shouldReceive('make')->with([
                'variable_value' => 'Test_SomeValue_' . $key,
            ], [
                'variable_value' => $variables[$key]->rules,
            ])->once()->andReturnSelf();
            $this->validator->shouldReceive('fails')->withNoArgs()->once()->andReturn(false);
        }

        $service = $this->getService();
        $service->setUserLevel(User::USER_LEVEL_ADMIN);
        $response = $service->handle(1, [
            $variables[0]->env_variable => 'Test_SomeValue_0',
            $variables[1]->env_variable => 'Test_SomeValue_1',
            $variables[2]->env_variable => 'Test_SomeValue_2',
            $variables[3]->env_variable => 'Test_SomeValue_3',
        ]);

        $this->assertNotEmpty($response);
        $this->assertInstanceOf(Collection::class, $response);
        $this->assertEquals(4, $response->count(), 'Assert response has all four items in collection.');

        $response->each(function ($variable, $key) use ($variables) {
            $this->assertObjectHasAttribute('id', $variable);
            $this->assertObjectHasAttribute('key', $variable);
            $this->assertObjectHasAttribute('value', $variable);
            $this->assertSame($variables[$key]->id, $variable->id);
            $this->assertSame($variables[$key]->env_variable, $variable->key);
            $this->assertSame('Test_SomeValue_' . $key, $variable->value);
        });
    }

    /**
     * Test that a DisplayValidationError is thrown when a variable is not validated.
     */
    public function testValidatorShouldThrowExceptionWhenAValidationErrorIsEncountered()
    {
        $variables = $this->getVariableCollection();
        $this->optionVariableRepository->shouldReceive('findWhere')->with([['egg_id', '=', 1]])->andReturn($variables);

        $this->validator->shouldReceive('make')->with([
            'variable_value' => null,
        ], [
            'variable_value' => $variables[0]->rules,
        ])->once()->andReturnSelf();
        $this->validator->shouldReceive('fails')->withNoArgs()->once()->andReturn(true);

        $this->validator->shouldReceive('errors')->withNoArgs()->once()->andReturnSelf();
        $this->validator->shouldReceive('toArray')->withNoArgs()->once()->andReturn([]);

        try {
            $this->getService()->handle(1, [$variables[0]->env_variable => null]);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(DisplayValidationException::class, $exception);

            $decoded = json_decode($exception->getMessage());
            $this->assertEquals(0, json_last_error(), 'Assert that response is decodable JSON.');
            $this->assertObjectHasAttribute('notice', $decoded);
            $this->assertEquals(
                trans('admin/server.exceptions.bad_variable', ['name' => $variables[0]->name]),
                $decoded->notice[0]
            );
        }
    }

    /**
     * Return a collection of fake variables to use for testing.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getVariableCollection(): Collection
    {
        return collect(
            [
                factory(EggVariable::class)->states('editable', 'viewable')->make(),
                factory(EggVariable::class)->states('viewable')->make(),
                factory(EggVariable::class)->states('editable')->make(),
                factory(EggVariable::class)->make(),
            ]
        );
    }

    /**
     * Return an instance of the service with mocked dependencies.
     *
     * @return \Pterodactyl\Services\Servers\VariableValidatorService
     */
    private function getService(): VariableValidatorService
    {
        return new VariableValidatorService(
            $this->optionVariableRepository,
            $this->serverRepository,
            $this->serverVariableRepository,
            $this->validator
        );
    }
}
