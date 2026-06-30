<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use phpseclib3\Crypt\EC;
use Pterodactyl\Models\User;
use Pterodactyl\Models\UserSSHKey;

class SSHKeyControllerTest extends ClientApiIntegrationTestCase
{
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

    public function testCertificateCannotBeStoredAsSSHKey()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/client/account/ssh-keys', [
            'name' => 'Name',
            'public_key' => $this->makeCertificate(),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.detail', 'The public key provided is not valid.');

        $this->assertEquals(0, $user->sshKeys()->count());
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

    protected function makeCertificate(): string
    {
        return <<<'PEM'
-----BEGIN CERTIFICATE-----
MIIClzCCAX+gAwIBAgIBADANBgkqhkiG9w0BAQUFADAPMQ0wCwYDVQQDDAR0ZXN0
MB4XDTI2MDYyODIxMzQzMFoXDTI2MDYyOTIxMzQzMFowDzENMAsGA1UEAwwEdGVz
dDCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALZNPzyWTEinfefSMYI8
jPtiDpQ3n4xnqobumxAMDSQd7Wkbi9MyWfV3tr7PIVzblC4aH5iLIy5dOhUWqyBd
LwYbmdGfeghnP261CrYw4npoBO+k1CoAtfjxuv5Mkz9zs4/BtknyqKxteLxLglJI
VTTl/IdGVdacvBSkfystMkK3AjvIwNseWLe2fcwMSs1k0yN/p/6NUYsO4BBkybaM
JF7s3s29nKZDwPn8HxYD/5cnStSI0nhcltYF5O7/6DiH4x5lvT4Z9D+aHppDMTur
yxAkMTSTZqMkE5iOtk6XrnEaXDVOci1fYFYIO0yKExnbf2DkB//W9f1wMYbO40Zc
jMkCAwEAATANBgkqhkiG9w0BAQUFAAOCAQEAAbbdgyP9X3kAgMdMo2yMX6jJC9Kl
NTio30d+NPCXsziA6elS2wWK7LhAVx0WRCho3KNJC2j3sKGOSXwf7HlG8yX4QPng
oMf91+yM95yhJZxVGelKfBGg34Wu1e8l0FcuCchmseR8QtwwuwScDXnYQV7PyRiW
OJ5KEg6opQivmfI4gJWDNbe6ALwHxR0TZeRITQ+tU0r/JCFwbjMX2pFNAl2sjE9x
iiZwIndK2bAsME622kuPgfx/osJ/8zuQhBeRsiLfUT44j2RJNRj99gXRfKAA0vyG
LaKfKLpXnF7mm6UuShG/HRt07bxu6Ayan/SJnv3E5ZkinR5lX0upcmM/gQ==
-----END CERTIFICATE-----
PEM;
    }
}
