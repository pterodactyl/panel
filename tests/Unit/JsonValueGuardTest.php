<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\JsonValueGuardTest;

use JsonException;
use Pterodactyl\Support\JsonEmptyObject;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Tests\TestCase;
use stdClass;
use UnexpectedValueException;

uses(TestCase::class);
test('decodes nested json values', function () {
    expect(JsonValueGuard::decode('{"enabled":true,"limits":[1,2.5,null]}'))->toBe(['enabled' => true, 'limits' => [1, 2.5, null]]);
});
test('accepts a caller defined maximum depth', function () {
    $this->expectException(UnexpectedValueException::class);
    JsonValueGuard::assertValue([['leaf']], 1);
});
test('decode enforces the caller defined maximum depth', function () {
    $this->expectException(UnexpectedValueException::class);

    JsonValueGuard::decode(nestedJson(2), 1);
});
test('decode8 accepts eight nested arrays', function () {
    expect(JsonValueGuard::decode8(nestedJson(8)))->toBe(nestedValue(8));
});
test('decode8 rejects nine nested arrays', function () {
    $this->expectException(UnexpectedValueException::class);

    JsonValueGuard::decode8(nestedJson(9));
});
test('rejects objects', function () {
    $this->expectException(UnexpectedValueException::class);
    JsonValueGuard::assertValue(new stdClass());
});
test('accepts json empty objects', function () {
    expect(fn () => JsonValueGuard::assertValue(new JsonEmptyObject()))->not->toThrow(UnexpectedValueException::class);
});
test('nullable strings preserve strings and null', function () {
    expect(JsonValueGuard::nullableString('value'))->toBe('value');
    expect(JsonValueGuard::nullableString(null))->toBeNull();
});
test('string lists preserve sequential string values', function () {
    expect(JsonValueGuard::stringList(['first', 'second']))->toBe(['first', 'second']);
});
test('string lists reject invalid shapes', function (mixed $value) {
    $this->expectException(UnexpectedValueException::class);

    JsonValueGuard::stringList($value);
})->with([
    'associative array' => [['name' => 'value']],
    'non-string item' => [['value', 1]],
]);
test('rejects invalid json', function () {
    $this->expectException(JsonException::class);
    JsonValueGuard::decode('{');
});
function nestedJson(int $depth): string
{
    return str_repeat('[', $depth).'"leaf"'.str_repeat(']', $depth);
}
function nestedValue(int $depth): array|string
{
    $value = 'leaf';
    for ($index = 0; $index < $depth; $index++) {
        $value = [$value];
    }

    return $value;
}
