<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Eggs\Variables\ReorderEggVariablesTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Eggs\ReordersEggVariables;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('an order given without the http api meets the same rules', function (array $order, array $fields): void {
    $egg = Egg::factory()->create();
    $variable = EggVariable::factory()->for($egg)->create(['sort_order' => 5]);

    try {
        // "id" stands in for the id of the egg's variable.
        $this->app->make(ReordersEggVariables::class)->reorder($egg, array_map(fn (mixed $id): mixed => $id === 'id' ? $variable->id : $id, $order));
        $this->fail('The variables were reordered.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe($fields);
    }

    expect($variable->refresh()->sort_order)->toBe(5);
})->with([
    'no ids' => [[], ['order']],
    'ids keyed by name' => [['first' => 'id'], ['order']],
    'an id that is not a number' => [['id', 'abc'], ['order.1']],
    'a whole float' => [[1.0], ['order.0']],
    'a boolean' => [[true], ['order.0']],
    'a null id' => [[null], ['order.0']],
    'the same id twice' => [['id', 'id'], ['order.0', 'order.1']],
]);

test('the variables take the sort order of their position, ids given as integers or numeric strings', function (): void {
    $egg = Egg::factory()->create();
    [$first, $second, $third] = EggVariable::factory()->for($egg)->count(3)->create()->all();
    $other = EggVariable::factory()->for(Egg::factory())->create(['sort_order' => 7]);

    $this->app->make(ReordersEggVariables::class)->reorder($egg, [(string) $third->id, $first->id, $other->id, (string) $second->id]);

    expect($third->refresh()->sort_order)->toBe(0)
        ->and($first->refresh()->sort_order)->toBe(1)
        ->and($second->refresh()->sort_order)->toBe(3)
        // A variable of another egg is skipped.
        ->and($other->refresh()->sort_order)->toBe(7);
});
