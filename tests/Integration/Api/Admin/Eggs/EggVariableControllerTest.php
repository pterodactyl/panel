<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Admin\Eggs\EggVariableControllerTest;

use Illuminate\Http\Response;
use Illuminate\Testing\Assert;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Tests\Integration\Api\Admin\AdminApiIntegrationTestCase;

uses(AdminApiIntegrationTestCase::class);
/** Endpoints that should return a 403 when accessed by a non-root-administrator. */
dataset('variableEndpointsDataProvider', fn (): array => [['getJson', 'api.admin.eggs.variables'], ['postJson', 'api.admin.eggs.variables.store'], ['putJson', 'api.admin.eggs.variables.reorder'], ['putJson', 'api.admin.eggs.variables.update'], ['delete', 'api.admin.eggs.variables.delete']]);
/** @return iterable<string, array{string, mixed, string}> */
dataset('invalidNormalizedValueProvider', function () {
    yield 'non-string default value' => ['default_value', ['nested'], 'default_value'];
    yield 'non-string option' => ['options', ['user_viewable', 42], 'options.1'];
    yield 'non-list options' => ['options', ['viewable' => 'user_viewable'], 'options'];
});
test('get variables', function (): void {
    $egg = createEgg();
    $variables = EggVariable::factory()->times(2)->create(['egg_id' => $egg->id]);
    $response = $this->getJson(url($egg));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(2, 'data');
    $response->assertJsonStructure(['object', 'data' => [['object', 'attributes' => ['id', 'egg_id', 'name', 'env_variable', 'default_value', 'user_viewable', 'user_editable', 'rules', 'created_at', 'updated_at']], ['object', 'attributes' => ['id', 'egg_id', 'name', 'env_variable', 'default_value', 'user_viewable', 'user_editable', 'rules', 'created_at', 'updated_at']]]]);
    Assert::assertArraySubset(['object' => 'egg_variable', 'attributes' => ['id' => $variables[0]->id, 'egg_id' => $variables[0]->egg_id, 'name' => $variables[0]->name, 'description' => $variables[0]->description, 'env_variable' => $variables[0]->env_variable, 'default_value' => $variables[0]->default_value, 'user_viewable' => $variables[0]->user_viewable, 'user_editable' => $variables[0]->user_editable, 'rules' => $variables[0]->rules]], collect($response->json('data'))->firstWhere('attributes.id', $variables[0]->id), true);
    Assert::assertArraySubset(['object' => 'egg_variable', 'attributes' => ['id' => $variables[1]->id, 'egg_id' => $variables[1]->egg_id, 'name' => $variables[1]->name, 'description' => $variables[1]->description, 'env_variable' => $variables[1]->env_variable, 'default_value' => $variables[1]->default_value, 'user_viewable' => $variables[1]->user_viewable, 'user_editable' => $variables[1]->user_editable, 'rules' => $variables[1]->rules]], collect($response->json('data'))->firstWhere('attributes.id', $variables[1]->id), true);
});
test('unknown include is ignored', function (): void {
    $egg = createEgg();
    EggVariable::factory()->create(['egg_id' => $egg->id]);
    $response = $this->getJson(route('api.admin.eggs.variables', ['egg' => $egg->id, 'include' => 'egg,nonexistent']));
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonCount(1, 'data');
});
test('create variable', function (): void {
    $egg = createEgg();
    $response = $this->postJson(url($egg), ['name' => 'Test Variable', 'description' => 'A brand new variable.', 'env_variable' => 'TEST_VARIABLE', 'options' => ['user_viewable', 'user_editable'], 'rules' => 'required|string', 'default_value' => 'default']);
    $response->assertStatus(Response::HTTP_CREATED);
    $response->assertJsonStructure(['object', 'attributes' => ['id', 'egg_id', 'name', 'env_variable', 'default_value', 'user_viewable', 'user_editable', 'rules', 'created_at', 'updated_at']]);
    $this->assertDatabaseHas('egg_variables', ['egg_id' => $egg->id, 'env_variable' => 'TEST_VARIABLE']);
    $variable = EggVariable::query()->where('egg_id', $egg->id)->firstOrFail();
    expect($variable->user_viewable)->toBeTrue();
    expect($variable->user_editable)->toBeTrue();

    $response->assertJson(['object' => 'egg_variable', 'attributes' => ['id' => $variable->id, 'egg_id' => $variable->egg_id, 'name' => $variable->name, 'description' => $variable->description, 'env_variable' => $variable->env_variable, 'default_value' => $variable->default_value, 'user_viewable' => $variable->user_viewable, 'user_editable' => $variable->user_editable, 'rules' => $variable->rules]]);
});
test('update variable', function (): void {
    $egg = createEgg();
    $variable = EggVariable::factory()->create(['egg_id' => $egg->id]);
    $response = $this->putJson(url($egg, $variable), ['name' => 'Updated Variable', 'description' => 'An updated description.', 'env_variable' => 'UPDATED_VARIABLE', 'options' => ['user_viewable'], 'rules' => 'required|string', 'default_value' => 'updated']);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('object', 'egg_variable');
    $response->assertJsonPath('attributes.name', 'Updated Variable');
    $response->assertJsonPath('attributes.env_variable', 'UPDATED_VARIABLE');
    $response->assertJsonPath('attributes.default_value', 'updated');
    $response->assertJsonPath('attributes.user_viewable', true);
    $response->assertJsonPath('attributes.user_editable', false);
    $this->assertDatabaseHas('egg_variables', ['id' => $variable->id, 'env_variable' => 'UPDATED_VARIABLE', 'name' => 'Updated Variable']);
});
test('delete variable', function (): void {
    $egg = createEgg();
    $variable = EggVariable::factory()->create(['egg_id' => $egg->id]);
    $this->assertDatabaseHas('egg_variables', ['id' => $variable->id]);
    $response = $this->delete(url($egg, $variable));
    $response->assertStatus(Response::HTTP_NO_CONTENT);
    $this->assertDatabaseMissing('egg_variables', ['id' => $variable->id]);
});
test('reorder variables', function (): void {
    $egg = createEgg();
    $a = EggVariable::factory()->create(['egg_id' => $egg->id]);
    $b = EggVariable::factory()->create(['egg_id' => $egg->id]);
    $c = EggVariable::factory()->create(['egg_id' => $egg->id]);
    $response = $this->putJson(route('api.admin.eggs.variables.reorder', ['egg' => $egg->id]), ['order' => [$c->id, $a->id, $b->id]]);
    $response->assertStatus(Response::HTTP_OK);
    $this->assertDatabaseHas('egg_variables', ['id' => $c->id, 'sort_order' => 0]);
    $this->assertDatabaseHas('egg_variables', ['id' => $a->id, 'sort_order' => 1]);
    $this->assertDatabaseHas('egg_variables', ['id' => $b->id, 'sort_order' => 2]);
    $response->assertJsonPath('data.0.attributes.id', $c->id);
    $response->assertJsonPath('data.1.attributes.id', $a->id);
    $response->assertJsonPath('data.2.attributes.id', $b->id);
});

test('variable reordering accepts numeric string identifiers', function (): void {
    $egg = createEgg();
    $first = EggVariable::factory()->for($egg)->create();
    $second = EggVariable::factory()->for($egg)->create();

    $this->putJson(route('api.admin.eggs.variables.reorder', ['egg' => $egg->id]), ['order' => [(string) $second->id, (string) $first->id]])
        ->assertOk()
        ->assertJsonPath('data.0.attributes.id', $second->id);

    $this->assertDatabaseHas('egg_variables', ['id' => $second->id, 'sort_order' => 0]);
    $this->assertDatabaseHas('egg_variables', ['id' => $first->id, 'sort_order' => 1]);
});

test('variable reordering rejects non-list or duplicate identifiers with 422', function (bool $associative): void {
    $egg = createEgg();
    $variable = EggVariable::factory()->for($egg)->create(['sort_order' => 5]);
    $order = $associative ? ['first' => $variable->id] : [$variable->id, $variable->id];

    $this->putJson(route('api.admin.eggs.variables.reorder', ['egg' => $egg->id]), ['order' => $order])
        ->assertUnprocessable();

    $this->assertDatabaseHas('egg_variables', ['id' => $variable->id, 'sort_order' => 5]);
})->with(['associative' => true, 'duplicates' => false]);
test('variable reordering rejects malformed identifier values with 422', function (bool|float|string|null $value): void {
    $egg = createEgg();
    $variable = EggVariable::factory()->for($egg)->create(['sort_order' => 5]);

    $this->putJson(route('api.admin.eggs.variables.reorder', ['egg' => $egg->id]), ['order' => [$value]], options: JSON_PRESERVE_ZERO_FRACTION)
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'order.0');

    $this->assertDatabaseHas('egg_variables', ['id' => $variable->id, 'sort_order' => 5]);
})->with(['boolean' => true, 'whole float' => 1.0, 'empty string' => '', 'null' => [null]]);
test('reserved name rejected', function (): void {
    $egg = createEgg();
    $response = $this->postJson(url($egg), ['name' => 'Reserved', 'env_variable' => 'SERVER_PORT', 'rules' => 'required|string', 'default_value' => 'default']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'env_variable');
    $this->assertDatabaseMissing('egg_variables', ['env_variable' => 'SERVER_PORT']);
});
test('update variable not on egg', function (): void {
    $egg = createEgg();
    $other = createEgg();
    $variable = EggVariable::factory()->create(['egg_id' => $other->id]);
    $response = $this->putJson(url($egg, $variable), ['name' => 'Updated Variable', 'env_variable' => 'UPDATED_VARIABLE', 'rules' => 'required|string', 'default_value' => 'updated']);
    $this->assertNotFoundJson($response);
});
test('delete variable not on egg', function (): void {
    $egg = createEgg();
    $other = createEgg();
    $variable = EggVariable::factory()->create(['egg_id' => $other->id]);
    $response = $this->delete(url($egg, $variable));
    $this->assertNotFoundJson($response);
});
test('non admin forbidden', function (string $method, string $routeName): void {
    $this->actingAsNonAdmin();
    $egg = createEgg();
    $variable = EggVariable::factory()->create(['egg_id' => $egg->id]);
    $url = route($routeName, ['egg' => $egg->id, 'variable' => $variable->id]);
    $response = $this->{$method}($url);
    $this->assertAccessDeniedJson($response);
})->with('variableEndpointsDataProvider');
test('invalid payloads return validation errors', function (): void {
    $egg = createEgg();
    $response = $this->postJson(url($egg), []);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonStructure(['errors' => [['code', 'detail', 'meta' => ['source_field', 'rule']]]]);

    $errors = collect($response->json('errors'));
    $error = $errors->firstWhere('meta.source_field', 'name');
    expect($error)->not->toBeNull('Expected a validation error for the [name] field.');
    expect($error['meta']['rule'])->toBe('required');
    expect($error['detail'])->not->toBeEmpty();
});
test('invalid normalized values are rejected', function (string $field, mixed $value, string $sourceField): void {
    $egg = createEgg();
    $payload = ['name' => 'Test Variable', 'env_variable' => 'TEST_VARIABLE', 'options' => ['user_viewable'], 'rules' => 'required|string', 'default_value' => 'default'];
    $payload[$field] = $value;
    $response = $this->postJson(url($egg), $payload);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

    expect(collect($response->json('errors'))->firstWhere('meta.source_field', $sourceField))->not->toBeNull(sprintf('Expected a validation error for the [%s] field.', $sourceField));
})->with('invalidNormalizedValueProvider');
/** Create a standalone egg for the variables under test. */
function createEgg(): Egg
{
    return Egg::factory()->create();
}

/** Build the variables route for a given egg, optionally targeting a single variable. */
function url(Egg $egg, ?EggVariable $variable = null): string
{
    return $variable instanceof EggVariable ? route('api.admin.eggs.variables.update', ['egg' => $egg->id, 'variable' => $variable->id]) : route('api.admin.eggs.variables', ['egg' => $egg->id]);
}

test('reserved name rejected regardless of case', function (): void {
    $egg = createEgg();
    $response = $this->postJson(url($egg), ['name' => 'Reserved', 'env_variable' => 'server_port', 'rules' => 'required|string', 'default_value' => 'default']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'env_variable');
    $this->assertDatabaseMissing('egg_variables', ['env_variable' => 'server_port']);
});
test('duplicate env variable on the same egg is rejected', function (): void {
    $egg = createEgg();
    EggVariable::factory()->create(['egg_id' => $egg->id, 'env_variable' => 'TAKEN']);
    $response = $this->postJson(url($egg), ['name' => 'Duplicate', 'env_variable' => 'TAKEN', 'rules' => 'required|string', 'default_value' => 'taken']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'env_variable');
    $response->assertJsonPath('errors.0.meta.rule', 'unique');
});
test('same env variable on another egg is allowed', function (): void {
    $egg = createEgg();
    EggVariable::factory()->create(['egg_id' => createEgg()->id, 'env_variable' => 'SHARED']);
    $response = $this->postJson(url($egg), ['name' => 'Shared', 'env_variable' => 'SHARED', 'rules' => 'required|string', 'default_value' => 'shared']);
    $response->assertStatus(Response::HTTP_CREATED);
    $this->assertDatabaseHas('egg_variables', ['egg_id' => $egg->id, 'env_variable' => 'SHARED']);
});
test('update keeps its own env variable without a uniqueness clash', function (): void {
    $egg = createEgg();
    $variable = EggVariable::factory()->create(['egg_id' => $egg->id, 'env_variable' => 'KEEP_ME']);
    $response = $this->putJson(url($egg, $variable), ['name' => 'Renamed', 'env_variable' => 'KEEP_ME', 'rules' => 'required|string', 'default_value' => 'kept']);
    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonPath('attributes.name', 'Renamed');
});
test('unresolvable rule string is rejected', function (): void {
    $egg = createEgg();
    $response = $this->postJson(url($egg), ['name' => 'Bad Rules', 'env_variable' => 'BAD_RULES', 'rules' => 'requird|string', 'default_value' => 'bad']);
    $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    $response->assertJsonPath('errors.0.meta.source_field', 'rules');
    $response->assertJsonPath('errors.0.detail', 'The validation rule "requird" is not a valid rule for this application.');
});
