<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Pterodactyl\Models\ApiKey;

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
        $key = ApiKey::factory()->create([
            'user_id' => $user->id,
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);

        $response = $this->actingAs($user)->get('/api/client/account/api-keys');

        $response->assertOk();
        $response->assertJson([
            'object' => 'list',
            'data' => [
                [
                    'object' => 'api_key',
                    'attributes' => [
                        'identifier' => $key->identifier,
                        'description' => $key->memo,
                        'allowed_ips' => $key->allowed_ips,
                        'last_used_at' => null,
                        'created_at' => $key->created_at->toIso8601String(),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test that an API key can be created for the client account. This also checks that the
     * API key secret is returned as metadata in the response since it will not be returned
     * after that point.
     */
    public function testApiKeyCanBeCreatedForAccount()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();

        // Small sub-test to ensure we're always comparing the  number of keys to the
        // specific logged in account, and not just the total number of keys stored in
        // the database.
        ApiKey::factory()->times(10)->create([
            'user_id' => User::factory()->create()->id,
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);

        $response = $this->actingAs($user)->postJson('/api/client/account/api-keys', [
            'description' => 'Test Description',
            'allowed_ips' => ['127.0.0.1'],
        ]);

        $response->assertOk();

        /** @var \Pterodactyl\Models\ApiKey $key */
        $key = ApiKey::query()->where('identifier', $response->json('attributes.identifier'))->firstOrFail();

        $response->assertJson([
            'object' => 'api_key',
            'attributes' => [
                'identifier' => $key->identifier,
                'description' => 'Test Description',
                'allowed_ips' => ['127.0.0.1'],
                'last_used_at' => null,
                'created_at' => $key->created_at->toIso8601String(),
            ],
            'meta' => [
                'secret_token' => decrypt($key->token),
            ],
        ]);
    }

    /**
     * Test that no more than 5 API keys can exist at any one time for an account. This prevents
     * a DoS attack vector against the panel.
     *
     * @see https://github.com/pterodactyl/panel/security/advisories/GHSA-pjmh-7xfm-r4x9
     */
    public function testNoMoreThanFiveApiKeysCanBeCreatedForAnAccount()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();
        ApiKey::factory()->times(5)->create([
            'user_id' => $user->id,
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);

        $response = $this->actingAs($user)->postJson('/api/client/account/api-keys', [
            'description' => 'Test Description',
            'allowed_ips' => ['127.0.0.1'],
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJsonPath('errors.0.code', 'DisplayException');
        $response->assertJsonPath('errors.0.detail', 'You have reached the account limit for number of API keys.');
    }

    /**
     * Test that a bad request results in a validation error being returned by the API.
     *
     * @see https://github.com/pterodactyl/panel/issues/2457
     */
    public function testValidationErrorIsReturnedForBadRequests()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/client/account/api-keys', [
            'description' => '',
            'allowed_ips' => ['127.0.0.1'],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('errors.0.meta.rule', 'required');
        $response->assertJsonPath('errors.0.detail', 'The description field is required.');

        $response = $this->actingAs($user)->postJson('/api/client/account/api-keys', [
            'description' => str_repeat('a', 501),
            'allowed_ips' => ['127.0.0.1'],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('errors.0.meta.rule', 'max');
        $response->assertJsonPath('errors.0.detail', 'The description may not be greater than 500 characters.');
    }

    /**
     * Tests that an API key can be deleted from the account.
     */
    public function testApiKeyCanBeDeleted()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();
        /** @var \Pterodactyl\Models\ApiKey $key */
        $key = ApiKey::factory()->create([
            'user_id' => $user->id,
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);

        $response = $this->actingAs($user)->delete('/api/client/account/api-keys/' . $key->identifier);
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('api_keys', ['id' => $key->id]);
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
        $key = ApiKey::factory()->create([
            'user_id' => $user2->id,
            'key_type' => ApiKey::TYPE_ACCOUNT,
        ]);

        $response = $this->actingAs($user)->delete('/api/client/account/api-keys/' . $key->identifier);
        $response->assertNotFound();

        $this->assertDatabaseHas('api_keys', ['id' => $key->id]);
    }

    /**
     * Tests that an application API key also belonging to the logged in user cannot be
     * deleted through this endpoint if it exists.
     */
    public function testApplicationApiKeyCannotBeDeleted()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();
        /** @var \Pterodactyl\Models\ApiKey $key */
        $key = ApiKey::factory()->create([
            'user_id' => $user->id,
            'key_type' => ApiKey::TYPE_APPLICATION,
        ]);

        $response = $this->actingAs($user)->delete('/api/client/account/api-keys/' . $key->identifier);
        $response->assertNotFound();

        $this->assertDatabaseHas('api_keys', ['id' => $key->id]);
    }
}
