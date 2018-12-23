<?php

namespace Tests\Unit\Services\Servers;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\User;
use Illuminate\Support\Collection;
use Pterodactyl\Models\EggVariable;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Services\Servers\VariableValidatorService;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface;

class VariableValidatorServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\EggVariableRepositoryInterface|\Mockery\Mock
     */
    private $optionVariableRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $serverRepository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerVariableRepositoryInterface|\Mockery\Mock
     */
    private $serverVariableRepository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->optionVariableRepository = m::mock(EggVariableRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->serverVariableRepository = m::mock(ServerVariableRepositoryInterface::class);
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

        try {
            $this->getService()->handle(1, [$variables[0]->env_variable => null]);
        } catch (ValidationException $exception) {
            $messages = $exception->validator->getMessageBag()->all();

            $this->assertNotEmpty($messages);
            $this->assertSame(2, count($messages));

            // We only expect to get the first two variables form the getVariableCollection
            // function here since those are the only two that are editable, and the others
            // should be discarded and not validated.
            for ($i = 0; $i < 2; $i++) {
                $this->assertSame(trans('validation.required', [
                    'attribute' => trans('validation.internal.variable_value', ['env' => $variables[$i]->name]),
                ]), $messages[$i]);
            }
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
                factory(EggVariable::class)->states('editable')->make(),
                factory(EggVariable::class)->states('viewable')->make(),
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
            $this->app->make(Factory::class)
        );
    }
}
