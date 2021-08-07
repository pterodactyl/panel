<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Pterodactyl\Models\PersonalAccessToken;
use Pterodactyl\Transformers\Api\Client\PersonalAccessTokenTransformer;

class ApiKeyControllerTest extends ClientApiIntegrationTestCase
{
    /**
     * Cleanup after tests.
     */
    protected function tearDown(): void
    {
        PersonalAccessToken::query()->forceDelete();

        parent::tearDown();
    }

    /**
     * Test that the client's API key can be returned successfully.
     */
    public function testApiKeysAreReturned()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test');
        $token = $token->accessToken;

        $response = $this->actingAs($user)->get('/api/client/account/api-keys');

        $response->assertOk();
        $response->assertJson([
            'object' => 'list',
            'data' => [
                [
                    'object' => 'personal_access_token',
                    'attributes' => [
                        'token_id' => $token->token_id,
                        'description' => $token->description,
                        'abilities' => ['*'],
                        'last_used_at' => null,
                        'updated_at' => $this->formatTimestamp($token->updated_at),
                        'created_at' => $this->formatTimestamp($token->created_at),
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
        PersonalAccessToken::factory()->times(10)->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/client/account/api-keys', [
            'description' => 'Test Description',
        ]);

        $response->assertOk();

        $key = PersonalAccessToken::query()->where('token_id', $response->json('attributes.token_id'))->firstOrFail();

        $response->assertJson([
            'object' => 'personal_access_token',
            'attributes' => (new PersonalAccessTokenTransformer())->transform($key),
        ]);

        $this->assertEquals($key->token, hash('sha256', substr($response->json('meta.secret_token'), 16)));
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
        PersonalAccessToken::factory()->times(10)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson('/api/client/account/api-keys', [
            'description' => 'Test Description',
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
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonPath('errors.0.meta.rule', 'required');
        $response->assertJsonPath('errors.0.detail', 'The description field is required.');

        $response = $this->actingAs($user)->postJson('/api/client/account/api-keys', [
            'description' => str_repeat('a', 501),
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
        $token = $user->createToken('test');

        $response = $this->actingAs($user)->delete('/api/client/account/api-keys/' . $token->accessToken->token_id);
        $response->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    /**
     * Test that trying to delete an API key that does not exist results in a 404.
     */
    public function testNonExistentApiKeyDeletionReturns404Error()
    {
        /** @var \Pterodactyl\Models\User $user */
        $user = User::factory()->create();
        $token = $user->createToken('test');

        $response = $this->actingAs($user)->delete('/api/client/account/api-keys/ptdl_1234');
        $response->assertNoContent();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
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
        $token = $user2->createToken('test');

        $response = $this->actingAs($user)->delete('/api/client/account/api-keys/' . $token->accessToken->token_id);
        $response->assertNoContent();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
    }
}
