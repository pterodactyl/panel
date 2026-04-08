<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use phpseclib3\Crypt\EC;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;

class SSHKeyControllerTest extends ClientApiIntegrationTestCase
{
    private const ED25519_SK_PUBLIC_KEY = 'sk-ssh-ed25519@openssh.com AAAAGnNrLXNzaC1lZDI1NTE5QG9wZW5zc2guY29tAAAAIJMm0fXS9aEgPF3rZ528wuD27ZzBgrKprvffaN5ECseMAAAABHNzaDo=';

    private const ECDSA_SK_PUBLIC_KEY = 'sk-ecdsa-sha2-nistp256@openssh.com AAAAInNrLWVjZHNhLXNoYTItbmlzdHAyNTZAb3BlbnNzaC5jb20AAAAIbmlzdHAyNTYAAABBBNjJTvrOinVU5vgN8pZidHlvRM4LfPDwPM5sB8dYHmjrGWz605iISV5e8gSLTQNraIIrjuFptlM6EvJ4FfYC+7kAAAAEc3NoOg==';

    /**
     * Test that only the SSH keys for the authenticated user are returned.
     */
    public function testSSHKeysAreReturned()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();

        $key = UserSSHKey::factory()->for($user)->create();
        UserSSHKey::factory()->for($user2)->rsa()->create();

        $this->actingAs($user);
        $response = $this->getJson('/api/client/account/ssh-keys')
            ->assertOk()
            ->assertJsonPath('object', 'list')
            ->assertJsonPath('data.0.object', UserSSHKey::RESOURCE_NAME);

        $this->assertJsonTransformedWith($response->json('data.0.attributes'), $key);
    }

    /**
     * Test that a user's SSH key can be deleted, and that passing the fingerprint
     * of another user's SSH key won't delete that key.
     */
    public function testSSHKeyCanBeDeleted()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();

        $key = UserSSHKey::factory()->for($user)->create();
        $key2 = UserSSHKey::factory()->for($user2)->create();

        $endpoint = '/api/client/account/ssh-keys/remove';

        $this->actingAs($user);
        $this->postJson($endpoint)
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.meta', ['source_field' => 'fingerprint', 'rule' => 'required']);

        $this->postJson($endpoint, ['fingerprint' => $key->fingerprint])->assertNoContent();

        $this->assertSoftDeleted($key);
        $this->assertNotSoftDeleted($key2);

        $this->postJson($endpoint, ['fingerprint' => $key->fingerprint])->assertNoContent();
        $this->postJson($endpoint, ['fingerprint' => $key2->fingerprint])->assertNoContent();

        $this->assertNotSoftDeleted($key2);
    }

    public function testDSAKeyIsRejected()
    {
        $user = User::factory()->create();
        $key = UserSSHKey::factory()->dsa()->make();

        $this->actingAs($user)->postJson('/api/client/account/ssh-keys', [
            'name' => 'Name',
            'public_key' => $key->public_key,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.detail', 'DSA keys are not supported.');

        $this->assertEquals(0, $user->sshKeys()->count());
    }

    public function testWeakRSAKeyIsRejected()
    {
        $user = User::factory()->create();
        $key = UserSSHKey::factory()->rsa(true)->make();

        $this->actingAs($user)->postJson('/api/client/account/ssh-keys', [
            'name' => 'Name',
            'public_key' => $key->public_key,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.detail', 'RSA keys must be at least 2048 bytes in length.');

        $this->assertEquals(0, $user->sshKeys()->count());
    }

    public function testInvalidOrPrivateKeyIsRejected()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/client/account/ssh-keys', [
            'name' => 'Name',
            'public_key' => 'invalid',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.detail', 'The public key provided is not valid.');

        $this->assertEquals(0, $user->sshKeys()->count());

        $key = EC::createKey('Ed25519');
        $this->actingAs($user)->postJson('/api/client/account/ssh-keys', [
            'name' => 'Name',
            'public_key' => $key->toString('PKCS8'),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.detail', 'The public key provided is not valid.');
    }

    public function testPublicKeyCanBeStored()
    {
        $user = User::factory()->create();
        $key = UserSSHKey::factory()->make();

        $this->actingAs($user)->postJson('/api/client/account/ssh-keys', [
            'name' => 'Name',
            'public_key' => $key->public_key,
        ])
            ->assertOk()
            ->assertJsonPath('object', UserSSHKey::RESOURCE_NAME)
            ->assertJsonPath('attributes.public_key', $key->public_key);

        $this->assertCount(1, $user->sshKeys);
        $this->assertEquals($key->public_key, $user->sshKeys[0]->public_key);
    }

    public function testEd25519SecurityKeyCanBeStored()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/client/account/ssh-keys', [
            'name' => 'Name',
            'public_key' => self::ED25519_SK_PUBLIC_KEY,
        ])
            ->assertOk()
            ->assertJsonPath('object', UserSSHKey::RESOURCE_NAME)
            ->assertJsonPath('attributes.public_key', self::ED25519_SK_PUBLIC_KEY);

        $this->assertCount(1, $user->sshKeys);
        $this->assertEquals(self::ED25519_SK_PUBLIC_KEY, $user->sshKeys[0]->public_key);
    }

    public function testEcdsaSecurityKeyCanBeStored()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/client/account/ssh-keys', [
            'name' => 'Name',
            'public_key' => self::ECDSA_SK_PUBLIC_KEY,
        ])
            ->assertOk()
            ->assertJsonPath('object', UserSSHKey::RESOURCE_NAME)
            ->assertJsonPath('attributes.public_key', self::ECDSA_SK_PUBLIC_KEY);

        $this->assertCount(1, $user->sshKeys);
        $this->assertEquals(self::ECDSA_SK_PUBLIC_KEY, $user->sshKeys[0]->public_key);
    }

    public function testPublicKeyThatAlreadyExistsCannotBeAddedASecondTime()
    {
        $user = User::factory()->create();
        $key = UserSSHKey::factory()->for($user)->create();

        $this->actingAs($user)->postJson('/api/client/account/ssh-keys', [
            'name' => 'Name',
            'public_key' => $key->public_key,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.detail', 'The public key provided already exists on your account.');

        $this->assertEquals(1, $user->sshKeys()->count());
    }
}
