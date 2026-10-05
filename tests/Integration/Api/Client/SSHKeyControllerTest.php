<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\SSHKeyControllerTest;

use phpseclib3\Crypt\EC;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
test('ssh keys are returned', function (): void {
    $user = User::factory()->create();
    $user2 = User::factory()->create();
    $key = UserSSHKey::factory()->for($user)->create();
    UserSSHKey::factory()->for($user2)->rsa()->create();
    $this->actingAs($user);
    $response = $this->getJson('/api/client/account/ssh-keys')->assertOk()->assertJsonPath('object', 'list')->assertJsonPath('data.0.object', UserSSHKey::RESOURCE_NAME);
    expect($response->json('data.0.attributes'))->toBe([
        'name' => $key->name,
        'fingerprint' => $key->fingerprint,
        'public_key' => $key->public_key,
        'created_at' => $key->created_at->toAtomString(),
    ]);
});
test('ssh key can be deleted', function (): void {
    $user = User::factory()->create();
    $user2 = User::factory()->create();
    $key = UserSSHKey::factory()->for($user)->create();
    $key2 = UserSSHKey::factory()->for($user2)->create();
    $endpoint = '/api/client/account/ssh-keys/remove';
    $this->actingAs($user);
    $this->postJson($endpoint)->assertUnprocessable()->assertJsonPath('errors.0.meta', ['source_field' => 'fingerprint', 'rule' => 'required']);
    $this->postJson($endpoint, ['fingerprint' => $key->fingerprint])->assertNoContent();
    $this->assertSoftDeleted($key);
    $this->assertNotSoftDeleted($key2);
    $this->postJson($endpoint, ['fingerprint' => $key->fingerprint])->assertNoContent();
    $this->postJson($endpoint, ['fingerprint' => $key2->fingerprint])->assertNoContent();
    $this->assertNotSoftDeleted($key2);
});
test('dsa key is rejected', function (): void {
    $user = User::factory()->create();
    $key = UserSSHKey::factory()->dsa()->make();
    $this->actingAs($user)->postJson('/api/client/account/ssh-keys', ['name' => 'Name', 'public_key' => $key->public_key])->assertUnprocessable()->assertJsonPath('errors.0.detail', 'DSA keys are not supported.');
    expect($user->sshKeys()->count())->toEqual(0);
});
test('weak rsa key is rejected', function (): void {
    $user = User::factory()->create();
    $key = UserSSHKey::factory()->rsa(true)->make();
    $this->actingAs($user)->postJson('/api/client/account/ssh-keys', ['name' => 'Name', 'public_key' => $key->public_key])->assertUnprocessable()->assertJsonPath('errors.0.detail', 'RSA keys must be at least 2048 bytes in length.');
    expect($user->sshKeys()->count())->toEqual(0);
});
test('invalid or private key is rejected', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/client/account/ssh-keys', ['name' => 'Name', 'public_key' => 'invalid'])->assertUnprocessable()->assertJsonPath('errors.0.detail', 'The public key provided is not valid.');
    expect($user->sshKeys()->count())->toEqual(0);
    $key = EC::createKey('Ed25519');
    $this->actingAs($user)->postJson('/api/client/account/ssh-keys', ['name' => 'Name', 'public_key' => $key->toString('PKCS8')])->assertUnprocessable()->assertJsonPath('errors.0.detail', 'The public key provided is not valid.');
});
test('only supported public key material can be stored', function (string $publicKey): void {
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/client/account/ssh-keys', ['name' => 'Name', 'public_key' => $publicKey])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.meta.source_field', 'public_key');
    expect($user->sshKeys()->count())->toBe(0);
})->with([
    'x509 certificate' => fn (): string => file_get_contents(base_path('tests/Fixtures/keys/x509-certificate.pem')),
    'key with a NUL byte' => fn (): string => substr_replace(EC::createKey('Ed25519')->getPublicKey()->toString('OpenSSH'), "\0", 20, 0),
    'key over 16 KB' => fn (): string => 'ssh-ed25519 '.str_repeat('A', 16400),
]);
test('public key can be stored', function (): void {
    $user = User::factory()->create();
    $key = UserSSHKey::factory()->make();
    $this->actingAs($user)->postJson('/api/client/account/ssh-keys', ['name' => 'Name', 'public_key' => $key->public_key])->assertOk()->assertJsonPath('object', UserSSHKey::RESOURCE_NAME)->assertJsonPath('attributes.public_key', $key->public_key);
    expect($user->sshKeys)->toHaveCount(1);
    expect($user->sshKeys[0]->public_key)->toEqual($key->public_key);
});
test('public key that already exists cannot be added a second time', function (): void {
    $user = User::factory()->create();
    $key = UserSSHKey::factory()->for($user)->create();
    $this->actingAs($user)->postJson('/api/client/account/ssh-keys', ['name' => 'Name', 'public_key' => $key->public_key])->assertUnprocessable()->assertJsonPath('errors.0.detail', 'The public key provided already exists on your account.');
    expect($user->sshKeys()->count())->toEqual(1);
});
