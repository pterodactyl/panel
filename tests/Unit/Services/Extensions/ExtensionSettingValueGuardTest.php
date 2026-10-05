<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Services\Extensions\ExtensionSettingValueGuardTest;

use Pterodactyl\Services\Extensions\ExtensionSettingValueGuard;
use Pterodactyl\Tests\TestCase;
use stdClass;
use UnexpectedValueException;

uses(TestCase::class);
test('delegates decoding and assertion to the json value guard', function () {
    expect(ExtensionSettingValueGuard::decode('{"enabled":true,"limits":[1,2.5,null]}'))->toBe(['enabled' => true, 'limits' => [1, 2.5, null]]);
    expect(fn () => ExtensionSettingValueGuard::assertValue(new stdClass()))->toThrow(UnexpectedValueException::class);
});
test('color accepts hex and oklch colours only', function (mixed $value, ?string $expected) {
    expect(ExtensionSettingValueGuard::color($value))->toBe($expected);
})->with([
    ['#FFF', '#fff'],
    ['#12ab', '#12ab'],
    [' #A1B2C3 ', '#a1b2c3'],
    ['#a1b2c3d4', '#a1b2c3d4'],
    ['oklch(0.623 0.188 259.8)', 'oklch(0.623 0.188 259.8)'],
    ["OKLCH(62.3%\t.188  259.8deg/0.5)", 'oklch(62.3% .188 259.8deg / 0.5)'],
    ['#12345', null],
    ['#ggg', null],
    ['blue', null],
    ['rgb(1 2 3)', null],
    ['oklch(0.6 0.1)', null],
    ['oklch(0.6 0.1 20 / 1 / 1)', null],
    ['oklch(var(--l) 0.1 20)', null],
    ['#fff;color:red', null],
    ["#fff\n}", null],
    [str_repeat('#', 65), null],
    [0xFFFFFF, null],
    [null, null],
    [['#fff'], null],
]);
test('text strings and choices coerce stored values to their field shape', function () {
    expect(ExtensionSettingValueGuard::text("a\r\nb\rc"))->toBe("a\nb\nc");
    expect(ExtensionSettingValueGuard::text(5))->toBeNull();
    expect(ExtensionSettingValueGuard::strings(['a', 1, null, 'b', ['c']]))->toBe(['a', 'b']);
    expect(ExtensionSettingValueGuard::strings(['x' => 'a']))->toBeNull();
    expect(ExtensionSettingValueGuard::strings('a'))->toBeNull();
    expect(ExtensionSettingValueGuard::choices(['b', '2', 'b', 'zz', true, ['a']], ['a', 'b', 2]))->toBe(['b', 2]);
    expect(ExtensionSettingValueGuard::choices(['x' => 'a'], ['a']))->toBeNull();
    expect(ExtensionSettingValueGuard::choices(null, ['a']))->toBeNull();
});
test('fileReference accepts only server generated names', function (mixed $value, bool $valid) {
    expect(ExtensionSettingValueGuard::fileReference($value) !== null)->toBe($valid);
})->with([
    ['0123456789abcdef0123456789abcdef01234567.png', true],
    ['0123456789abcdef0123456789abcdef01234567.woff2', true],
    ['0123456789ABCDEF0123456789abcdef01234567.png', false],
    ['0123456789abcdef0123456789abcdef01234567.php', false],
    ['0123456789abcdef0123456789abcdef01234567.png.php', false],
    ['../0123456789abcdef0123456789abcdef01234567.png', false],
    ["0123456789abcdef0123456789abcdef01234567.png\n", false],
    ['logo.png', false],
    [null, false],
    [['0123456789abcdef0123456789abcdef01234567.png'], false],
]);
test('assertValues accepts string-keyed objects of valid values', function () {
    expect(fn () => ExtensionSettingValueGuard::assertValues(['enabled' => true, 'limits' => [1, 2.5, null]]))->not->toThrow(UnexpectedValueException::class);
});
test('assertValues rejects invalid shapes', function (mixed $value) {
    expect(fn () => ExtensionSettingValueGuard::assertValues($value))->toThrow(UnexpectedValueException::class);
})->with([
    'non-array' => ['enabled'],
    'non-string key' => [[true]],
    'nested invalid value' => [['limits' => new stdClass()]],
]);
