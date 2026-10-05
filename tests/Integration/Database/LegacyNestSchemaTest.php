<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Database\LegacyNestSchemaTest;

use Illuminate\Support\Facades\Schema;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class);
/**
 * @return array<string, array{string, string}>
 */
dataset('legacyNestColumnsDataProvider', function () {
    return ['servers.nest_id' => ['servers', 'nest_id'], 'eggs.nest_id' => ['eggs', 'nest_id'], 'api_keys.r_nests' => ['api_keys', 'r_nests'], 'tags.legacy_nest_id' => ['tags', 'legacy_nest_id']];
});
test('nest tables survive migration', function () {
    expect(Schema::hasTable('nests'))->toBeTrue('The legacy nests table must not be dropped.');
});
test('legacy nest columns survive migration', function (string $table, string $column) {
    expect(Schema::hasColumn($table, $column))->toBeTrue("The legacy {$table}.{$column} column must not be dropped — nest data is retained on purpose.");
})->with('legacyNestColumnsDataProvider');
test('unwritten nest columns are nullable', function () {
    foreach ([['servers', 'nest_id'], ['eggs', 'nest_id']] as [$table, $column]) {
        $definition = collect(Schema::getColumns($table))->firstWhere('name', $column);
        expect($definition)->not->toBeNull("Expected {$table}.{$column} to exist.");
        expect($definition['nullable'])->toBeTrue("{$table}.{$column} must be nullable; nothing writes it any more.");
    }
});
