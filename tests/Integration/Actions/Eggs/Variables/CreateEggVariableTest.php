<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Eggs\Variables\CreateEggVariableTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Eggs\CreatesEggVariables;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

/** @return array<string, mixed> */
function variable(array $overrides = []): array
{
    return [...['name' => 'Server Jar', 'env_variable' => 'SERVER_JARFILE', 'rules' => 'required|string', 'default_value' => 'server.jar'], ...$overrides];
}

test('variables created without the http api meet the same rules', function (array $input, string $field): void {
    $egg = Egg::factory()->create();
    EggVariable::factory()->for($egg)->create(['env_variable' => 'TAKEN']);

    try {
        $this->app->make(CreatesEggVariables::class)->create($egg, variable($input));
        $this->fail('The variable was created.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe([$field]);
    }

    expect($egg->variables()->count())->toBe(1);
})->with([
    'a missing name' => [['name' => null], 'name'],
    'a description that is not a string' => [['description' => ['text']], 'description'],
    'a missing environment variable' => [['env_variable' => ''], 'env_variable'],
    'an environment variable with a dash' => [['env_variable' => 'SERVER-JAR'], 'env_variable'],
    'a reserved environment variable' => [['env_variable' => 'server_port'], 'env_variable'],
    'an environment variable the egg already has' => [['env_variable' => 'TAKEN'], 'env_variable'],
    'a rule laravel does not know' => [['rules' => 'requird|string'], 'rules'],
    'no default value' => [['default_value' => null], 'default_value'],
    'options that are not a list' => [['options' => ['viewable' => 'user_viewable']], 'options'],
    'an option that is not a string' => [['options' => ['user_viewable', 42]], 'options.1'],
    'an empty list of options' => [['options' => []], 'options'],
]);

test('the options set whether users can see and edit the variable', function (?array $options, bool $viewable, bool $editable): void {
    $egg = Egg::factory()->create();

    $variable = $this->app->make(CreatesEggVariables::class)->create($egg, variable($options === null ? [] : ['options' => $options]));

    expect($variable->egg_id)->toBe($egg->id)
        ->and($variable->user_viewable)->toBe($viewable)
        ->and($variable->user_editable)->toBe($editable)
        ->and($variable->description)->toBe('');
})->with([
    'none given' => [null, false, false],
    'viewable' => [['user_viewable'], true, false],
    'both, with an option nothing reads' => [['user_editable', 'unknown', 'user_viewable'], true, true],
]);

test('an environment variable another egg has is free to use', function (): void {
    EggVariable::factory()->for(Egg::factory())->create(['env_variable' => 'SHARED']);
    $egg = Egg::factory()->create();

    $variable = $this->app->make(CreatesEggVariables::class)->create($egg, variable(['env_variable' => 'SHARED', 'description' => 'Shared.']));

    expect($variable->env_variable)->toBe('SHARED')
        ->and($variable->description)->toBe('Shared.');
});
