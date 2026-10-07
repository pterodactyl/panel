<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\ServerCreationDataTest;

use Pterodactyl\Data\ServerCreationData;
use Pterodactyl\Support\JsonEmptyObject;
use Pterodactyl\Tests\TestCase;
use UnexpectedValueException;

uses(TestCase::class);
test('normalizes validated scalar values', function () {
    expect(ServerCreationData::parse(validData()))->toBe(['external_id' => 'external-123', 'name' => 'Example server', 'description' => 'Provisioned by the API', 'owner_id' => 10, 'egg_id' => 20, 'image' => 'ghcr.io/example/server:latest', 'startup' => './server', 'environment' => ['MODE' => 'production', 'QUERY_PORT' => 25565], 'memory' => 1024, 'swap' => 0, 'disk' => 4096, 'io' => 500, 'cpu' => 100, 'threads' => '0-3', 'skip_scripts' => true, 'allocation_id' => 1, 'allocation_additional' => [2, 3], 'start_on_completion' => true, 'database_limit' => 1, 'allocation_limit' => 2, 'backup_limit' => 3, 'oom_disabled' => false, 'extensions' => ['billing' => ['plan' => 'pro']]]);
});
test('defaults omitted optional booleans to false', function () {
    $data = validData();
    unset($data['skip_scripts'], $data['start_on_completion']);

    $parsed = ServerCreationData::parse($data);

    expect($parsed['skip_scripts'])->toBeFalse();
    expect($parsed['start_on_completion'])->toBeFalse();
});
test('normalizes numeric boolean zero to false', function () {
    $data = validData();
    $data['skip_scripts'] = 0;
    $data['start_on_completion'] = 0;

    $parsed = ServerCreationData::parse($data);

    expect($parsed['skip_scripts'])->toBeFalse();
    expect($parsed['start_on_completion'])->toBeFalse();
});
test('rejects non scalar environment values', function () {
    $data = validData();
    $data['environment'] = ['INVALID' => ['nested']];
    $this->expectException(UnexpectedValueException::class);
    ServerCreationData::parse($data);
});
test('rejects invalid typed fields', function (string $field, mixed $value, string $expectedType) {
    $data = validData();
    $data[$field] = $value;
    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage(sprintf('Validated server creation field "%s" must be %s.', $field, $expectedType));

    ServerCreationData::parse($data);
})->with([
    'non-integer owner' => ['owner_id', '10.5', 'an integer'],
    'associative allocation list' => ['allocation_additional', ['first' => 2], 'a list of integers'],
    'empty object environment value' => ['environment', ['INVALID' => new JsonEmptyObject()], 'an object of scalar values'],
]);
/** @return array<string, JsonValue> */
function validData(): array
{
    return ['external_id' => 'external-123', 'name' => 'Example server', 'description' => 'Provisioned by the API', 'owner_id' => '10', 'egg_id' => '20', 'image' => 'ghcr.io/example/server:latest', 'startup' => './server', 'environment' => ['MODE' => 'production', 'QUERY_PORT' => 25565], 'memory' => '1024', 'swap' => '0', 'disk' => '4096', 'io' => '500', 'cpu' => '100', 'threads' => '0-3', 'skip_scripts' => true, 'allocation_id' => '1', 'allocation_additional' => ['2', 3], 'start_on_completion' => 1, 'database_limit' => '1', 'allocation_limit' => 2, 'backup_limit' => 3, 'oom_disabled' => false, 'extensions' => ['billing' => ['plan' => 'pro']]];
}
