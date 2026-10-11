<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Actions\Users\CreateSshKeyTest;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Users\CreatesSshKeys;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;
use Pterodactyl\Tests\Integration\IntegrationTestCase;

uses(IntegrationTestCase::class, DatabaseTransactions::class);

test('ssh keys added without the http api meet the same rules', function (string $name, bool $blankKey, bool $blankFingerprint, array $fields): void {
    $user = User::factory()->create();
    $key = UserSSHKey::factory()->make();

    try {
        $this->app->make(CreatesSshKeys::class)->create($user, $name, $blankKey ? '' : $key->public_key, $blankFingerprint ? '  ' : $key->fingerprint);
        $this->fail('The key was added.');
    } catch (ValidationException $validationException) {
        expect(array_keys($validationException->errors()))->toBe($fields);
    }

    expect($user->sshKeys()->exists())->toBeFalse();
})->with([
    'a blank name' => ['', false, false, ['name']],
    'a name of spaces' => ['   ', false, false, ['name']],
    'a blank public key' => ['Laptop', true, false, ['public_key']],
    'a blank fingerprint' => ['Laptop', false, true, ['fingerprint']],
    'nothing at all' => ['', true, true, ['name', 'public_key', 'fingerprint']],
]);

test('a key is stored with the name, key and fingerprint it is given', function (): void {
    $user = User::factory()->create();
    $key = UserSSHKey::factory()->make();

    $created = $this->app->make(CreatesSshKeys::class)->create($user, 'Laptop', $key->public_key, $key->fingerprint);

    expect($created->user_id)->toBe($user->id)
        ->and($created->name)->toBe('Laptop')
        ->and($created->public_key)->toBe($key->public_key)
        ->and($created->fingerprint)->toBe($key->fingerprint);
});
