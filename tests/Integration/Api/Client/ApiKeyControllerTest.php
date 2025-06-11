<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Pterodactyl\Models\ApiKey;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Events\ActivityLogged;

class ApiKeyControllerTest extends ClientApiIntegrationTestCase
{
    /**
     * Cleanup after tests.
     */
    protected function tearDown(): void
    {
        ApiKey::query()->forceDelete();

        parent::tearDown();
    }

    /**
     * Test that the client's API key can be returned successfully.
     */
    public function testApiKeysAreReturned()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();
        /** @var \Pterodactyl\Models\ApiKey $key */
        $key = ApiKey::factory()->for($user)->create([
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);

        $response = $this->actingAs($user)->get('/api/client/account/api-keys')
            ->assertOk()
            ->assertJsonPath('object', 'list')
            ->assertJsonPath('data.0.object', ApiKey::RESOURCE_NAME);

        $this->assertJsonTransformedWith($response->json('data.0.attributes'), $key);
    }

    /**
     * Test that an API key can be created for the client account. This also checks that the
     * API key secret is returned as metadata in the response since it will not be returned
     * after that point.
     *
     * @dataProvider validIPAddressDataProvider
     */
    public function testApiKeyCanBeCreatedForAccount(array $data)
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();

        // Small subtest to ensure we're always comparing the  number of keys to the
        // specific logged in account, and not just the total number of keys stored in
        // the database.
        ApiKey::factory()->times(10)->create([
            'user_id' => User::factory()->create()->id,
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);

        $response = $this->actingAs($user)->postJson('/api/client/account/api-keys', [
            'description' => 'Test Description',
            'allowed_ips' => $data,
        ])
            ->assertOk()
            ->assertJsonPath('object', ApiKey::RESOURCE_NAME);

        /** @var \Pterodactyl\Models\ApiKey $key */
        $key = ApiKey::query()->where('identifier', $response->json('attributes.identifier'))->firstOrFail();

        $this->assertJsonTransformedWith($response->json('attributes'), $key);
        $response->assertJsonPath('meta.secret_token', decrypt($key->token));

        $this->assertActivityFor('user:api-key.create', $user, [$key, $user]);
    }

    /**
     * Block requests to create an API key specifying more than 50 IP addresses.
     */
    public function testApiKeyCannotSpecifyMoreThanFiftyIps()
    {
        $ips = [];
        for ($i = 0; $i < 100; ++$i) {
            $ips[] = '127.0.0.' . $i;
        }

        $this->actingAs(User::factory()->create())
            ->postJson('/api/client/account/api-keys', [
                'description' => 'Test Data',
                'allowed_ips' => $ips,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.detail', 'The allowed ips may not have more than 50 items.');
    }

    /**
     * Test that no more than 25 API keys can exist at any one time for an account. This prevents
     * a DoS attack vector against the panel.
     *
     * @see https://github.com/pterodactyl/panel/security/advisories/GHSA-pjmh-7xfm-r4x9
     * @see https://github.com/pterodactyl/panel/issues/4394
     */
    public function testApiKeyLimitIsApplied()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();
        ApiKey::factory()->times(25)->for($user)->create([
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);

        $this->actingAs($user)->postJson('/api/client/account/api-keys', [
            'description' => 'Test Description',
            'allowed_ips' => ['127.0.0.1'],
        ])
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonPath('errors.0.code', 'DisplayException')
            ->assertJsonPath('errors.0.detail', 'You have reached the account limit for number of API keys.');
    }

    /**
     * Test that a bad request results in a validation error being returned by the API.
     *
     * @see https://github.com/pterodactyl/panel/issues/2457
     */
    public function testValidationErrorIsReturnedForBadRequests()
    {
        $this->actingAs(User::factory()->create());

        $this->postJson('/api/client/account/api-keys', [
            'description' => '',
            'allowed_ips' => ['127.0.0.1'],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.meta.rule', 'required')
            ->assertJsonPath('errors.0.detail', 'The description field is required.');

        $this->postJson('/api/client/account/api-keys', [
            'description' => str_repeat('a', 501),
            'allowed_ips' => ['127.0.0.1'],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.meta.rule', 'max')
            ->assertJsonPath('errors.0.detail', 'The description may not be greater than 500 characters.');

        $this->postJson('/api/client/account/api-keys', [
                'description' => 'Foobar',
                'allowed_ips' => ['hodor', '127.0.0.1', 'hodor/24'],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.detail', '"hodor" is not a valid IP address or CIDR range.')
            ->assertJsonPath('errors.0.meta.source_field', 'allowed_ips.0')
            ->assertJsonPath('errors.1.detail', '"hodor/24" is not a valid IP address or CIDR range.')
            ->assertJsonPath('errors.1.meta.source_field', 'allowed_ips.2');
    }

    /**
     * Tests that an API key can be deleted from the account.
     */
    public function testApiKeyCanBeDeleted()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();
        /** @var \Pterodactyl\Models\ApiKey $key */
        $key = ApiKey::factory()->for($user)->create([
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);

        $response = $this->actingAs($user)->delete('/api/client/account/api-keys/' . $key->identifier);
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('api_keys', ['id' => $key->id]);
        $this->assertActivityFor('user:api-key.delete', $user, $user);
    }

    /**
     * Test that trying to delete an API key that does not exist results in a 404.
     */
    public function testNonExistentApiKeyDeletionReturns404Error()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();
        /** @var \Pterodactyl\Models\ApiKey $key */
        $key = ApiKey::factory()->create([
            'user_id' => $user->id,
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);

        $response = $this->actingAs($user)->delete('/api/client/account/api-keys/1234');
        $response->assertNotFound();

        $this->assertDatabaseHas('api_keys', ['id' => $key->id]);
        Event::assertNotDispatched(ActivityLogged::class);
    }

    /**
     * Test that an API key that exists on the system cannot be deleted if the user
     * who created it is not the authenticated user.
     */
    public function testApiKeyBelongingToAnotherUserCannotBeDeleted()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();
        /** @var \Pterodactyl\Models\User $user2 */
        $user2 = User::factory()->create();
        /** @var \Pterodactyl\Models\ApiKey $key */
        $key = ApiKey::factory()->for($user2)->create([
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/client/account/api-keys/' . $key->identifier)
            ->assertNotFound();

        $this->assertDatabaseHas('api_keys', ['id' => $key->id]);
        Event::assertNotDispatched(ActivityLogged::class);
    }

    /**
     * Tests that an application API key also belonging to the logged-in user cannot be
     * deleted through this endpoint if it exists.
     */
    public function testApplicationApiKeyCannotBeDeleted()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();
        /** @var \Pterodactyl\Models\ApiKey $key */
        $key = ApiKey::factory()->for($user)->create([
            'key_type' => ApiKey::TYPE_APPLICATION,
        ]);

        $this->actingAs($user)
            ->deleteJson('/api/client/account/api-keys/' . $key->identifier)
            ->assertNotFound();

        $this->assertDatabaseHas('api_keys', ['id' => $key->id]);
    }

    /**
     * Provides some different IP address combinations that can be used when
     * testing that we accept the expected IP values.
     */
    public static function validIPAddressDataProvider(): array
    {
        return [
            [[]],
            [['127.0.0.1']],
            [['127.0.0.1', '::1']],
            [['::ffff:7f00:1']],
            [['127.0.0.1', '192.168.1.100', '192.168.10.10/28']],
            [['127.0.0.1/32', '192.168.100.100/27', '::1', '::1/128']],
        ];
    }
}
