<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Unit\Rules\UsernameTest;

use Pterodactyl\Rules\Username;
use Pterodactyl\Tests\TestCase;

uses(TestCase::class);
/**
 * Provide valid usernames.
 */
dataset('validUsernameDataProvider', function () {
    return [['username'], ['user_name'], ['user.name'], ['user-name'], ['123username123'], ['123-user.name'], ['123456']];
});
/**
 * Provide invalid usernames.
 */
dataset('invalidUsernameDataProvider', function () {
    return [['_username'], ['username_'], ['_username_'], ['-username'], ['.username'], ['username-'], ['username.'], ['user*name'], ['user^name'], ['user#name'], ['user+name'], ['1234_']];
});
test('valid usernames', function (string $username) {
    expect(passes($username))->toBeTrue('Assert username is valid.');
})->with('validUsernameDataProvider');
test('invalid usernames', function (string $username) {
    expect(passes($username))->toBeFalse('Assert username is not valid.');
})->with('invalidUsernameDataProvider');
function passes(string $username): bool
{
    $failed = false;
    (new Username)->validate('test', $username, function () use (&$failed): void {
        $failed = true;
    });

    return ! $failed;
}
