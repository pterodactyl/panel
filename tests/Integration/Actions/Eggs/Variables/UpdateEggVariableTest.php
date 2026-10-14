<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Eggs\Variables\UpdateEggVariableTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Eggs\UpdatesEggVariables;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, mixed> */
function variable(array $overrides = []): array
{
    return [...['name' => 'Renamed', 'env_variable' => 'KEEP_ME', 'rules' => 'required|string', 'default_value' => 'kept'], ...$overrides];
}

test('variables updated without the http api meet the same rules', function (array $input, string $field): void {
    $egg = Egg::factory()->create();
    EggVariable::factory()->for($egg)->create(['env_variable' => 'TAKEN']);
    $variable = EggVariable::factory()->for($egg)->create(['env_variable' => 'KEEP_ME'])->refresh();
    $before = $variable->getAttributes();

    try {
        $this->app->make(UpdatesEggVariables::class)->update($variable, variable($input));
        $this->fail('The variable was updated.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($variable->fresh()->getAttributes())->toBe($before);
})->with([
    'a missing name' => [['name' => ''], 'name'],
    'an environment variable another variable of the egg has' => [['env_variable' => 'TAKEN'], 'env_variable'],
    'a reserved environment variable' => [['env_variable' => 'STARTUP'], 'env_variable'],
    'a default value that is not a string' => [['default_value' => ['nested']], 'default_value'],
    'a rule laravel does not know' => [['rules' => 'string|requird'], 'rules'],
]);

test('a variable keeps its own environment variable and takes the fields it is given', function (): void {
    $egg = Egg::factory()->create();
    $variable = EggVariable::factory()->for($egg)->viewable()->editable()->create(['env_variable' => 'KEEP_ME', 'description' => 'Old.']);

    $this->app->make(UpdatesEggVariables::class)->update($variable, variable(['options' => ['user_editable']]));

    $variable->refresh();
    expect($variable->name)->toBe('Renamed')
        ->and($variable->env_variable)->toBe('KEEP_ME')
        ->and($variable->default_value)->toBe('kept')
        // Left out, the description is blanked; an option left out is unset.
        ->and($variable->description)->toBe('')
        ->and($variable->user_viewable)->toBeFalse()
        ->and($variable->user_editable)->toBeTrue();
});

test('a variable can take an environment variable another egg has', function (): void {
    EggVariable::factory()->for(Egg::factory())->create(['env_variable' => 'SHARED']);
    $variable = EggVariable::factory()->for(Egg::factory())->create();

    $this->app->make(UpdatesEggVariables::class)->update($variable, variable(['env_variable' => 'SHARED']));

    expect($variable->refresh()->env_variable)->toBe('SHARED');
});
