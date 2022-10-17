<?php

namespace Pterodactyl\Tests\Integration\Api\Application\Users;

use Pterodactyl\Models\User;
use Illuminate\Http\Response;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Transformers\Api\Application\UserTransformer;
use Pterodactyl\Transformers\Api\Application\ServerTransformer;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

class UserControllerTest extends ApplicationApiIntegrationTestCase
{
    /**
     * Test the response when requesting all users on the panel.
     */
    public function testGetUsers()
    {
        $user = User::factory()->create();

        $response = $this->getJson('/api/application/users?per_page=60');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure([
            'object',
            'data' => [
                ['object', 'attributes' => ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at']],
                ['object', 'attributes' => ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at']],
            ],
            'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']],
        ]);

        $response
            ->assertJson([
                'object' => 'list',
                'data' => [[], []],
                'meta' => [
                    'pagination' => [
                        'total' => 2,
                        'count' => 2,
                        'per_page' => 60,
                        'current_page' => 1,
                        'total_pages' => 1,
                    ],
                ],
            ])
            ->assertJsonFragment([
                'object' => 'user',
                'attributes' => [
                    'id' => $this->getApiUser()->id,
                    'external_id' => $this->getApiUser()->external_id,
                    'uuid' => $this->getApiUser()->uuid,
                    'username' => $this->getApiUser()->username,
                    'email' => $this->getApiUser()->email,
                    'first_name' => $this->getApiUser()->name_first,
                    'last_name' => $this->getApiUser()->name_last,
                    'language' => $this->getApiUser()->language,
                    'root_admin' => $this->getApiUser()->root_admin,
                    '2fa' => (bool) $this->getApiUser()->totp_enabled,
                    'created_at' => $this->formatTimestamp($this->getApiUser()->created_at),
                    'updated_at' => $this->formatTimestamp($this->getApiUser()->updated_at),
                ],
            ])
            ->assertJsonFragment([
                'object' => 'user',
                'attributes' => [
                    'id' => $user->id,
                    'external_id' => $user->external_id,
                    'uuid' => $user->uuid,
                    'username' => $user->username,
                    'email' => $user->email,
                    'first_name' => $user->name_first,
                    'last_name' => $user->name_last,
                    'language' => $user->language,
                    'root_admin' => (bool) $user->root_admin,
                    '2fa' => (bool) $user->totp_enabled,
                    'created_at' => $this->formatTimestamp($user->created_at),
                    'updated_at' => $this->formatTimestamp($user->updated_at),
                ],
            ]);
    }

    /**
     * Test getting a single user.
     */
    public function testGetSingleUser()
    {
        $user = User::factory()->create();

        $response = $this->getJson('/api/application/users/' . $user->id);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2);
        $response->assertJsonStructure([
            'object',
            'attributes' => ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at'],
        ]);

        $response->assertJson([
            'object' => 'user',
            'attributes' => [
                'id' => $user->id,
                'external_id' => $user->external_id,
                'uuid' => $user->uuid,
                'username' => $user->username,
                'email' => $user->email,
                'first_name' => $user->name_first,
                'last_name' => $user->name_last,
                'language' => $user->language,
                'root_admin' => (bool) $user->root_admin,
                '2fa' => (bool) $user->totp_enabled,
                'created_at' => $this->formatTimestamp($user->created_at),
                'updated_at' => $this->formatTimestamp($user->updated_at),
            ],
        ]);
    }

    /**
     * Test that the correct relationships can be loaded.
     */
    public function testRelationshipsCanBeLoaded()
    {
        $user = User::factory()->create();
        $server = $this->createServerModel(['user_id' => $user->id]);

        $response = $this->getJson('/api/application/users/' . $user->id . '?include=servers');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2);
        $response->assertJsonStructure([
            'object',
            'attributes' => [
                'id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at',
                'relationships' => ['servers' => ['object', 'data' => [['object', 'attributes' => []]]]],
            ],
        ]);

        $response->assertJsonFragment([
            'object' => 'list',
            'data' => [
                [
                    'object' => 'server',
                    'attributes' => $this->getTransformer(ServerTransformer::class)->transform($server),
                ],
            ],
        ]);
    }

    /**
     * Test that attempting to load a relationship that the key does not have permission
     * for returns a null object.
     */
    public function testKeyWithoutPermissionCannotLoadRelationship()
    {
        $this->createNewDefaultApiKey($this->getApiUser(), ['r_servers' => 0]);

        $user = User::factory()->create();
        $this->createServerModel(['user_id' => $user->id]);

        $response = $this->getJson('/api/application/users/' . $user->id . '?include=servers');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2)->assertJsonCount(1, 'attributes.relationships');
        $response->assertJsonStructure([
            'attributes' => [
                'relationships' => [
                    'servers' => ['object', 'attributes'],
                ],
            ],
        ]);

        // Just assert that we see the expected relationship IDs in the response.
        $response->assertJson([
            'attributes' => [
                'relationships' => [
                    'servers' => [
                        'object' => 'null_resource',
                        'attributes' => null,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test that an invalid external ID returns a 404 error.
     */
    public function testGetMissingUser()
    {
        $response = $this->getJson('/api/application/users/nil');
        $this->assertNotFoundJson($response);
    }

    /**
     * Test that an authentication error occurs if a key does not have permission
     * to access a resource.
     */
    public function testErrorReturnedIfNoPermission()
    {
        $user = User::factory()->create();
        $this->createNewDefaultApiKey($this->getApiUser(), ['r_users' => 0]);

        $response = $this->getJson('/api/application/users/' . $user->id);
        $this->assertAccessDeniedJson($response);
    }

    /**
     * Test that a user can be created.
     */
    public function testCreateUser()
    {
        $response = $this->postJson('/api/application/users', [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonCount(3);
        $response->assertJsonStructure([
            'object',
            'attributes' => ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at'],
            'meta' => ['resource'],
        ]);

        $this->assertDatabaseHas('users', ['username' => 'testuser', 'email' => 'test@example.com']);

        $user = User::where('username', 'testuser')->first();
        $response->assertJson([
            'object' => 'user',
            'attributes' => $this->getTransformer(UserTransformer::class)->transform($user),
            'meta' => [
                'resource' => route('api.application.users.view', $user->id),
            ],
        ], true);
    }

    /**
     * Test that a user can be updated.
     */
    public function testUpdateUser()
    {
        $user = User::factory()->create();

        $response = $this->patchJson('/api/application/users/' . $user->id, [
            'username' => 'new.test.name',
            'email' => 'new@emailtest.com',
            'first_name' => $user->name_first,
            'last_name' => $user->name_last,
        ]);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2);
        $response->assertJsonStructure([
            'object',
            'attributes' => ['id', 'external_id', 'uuid', 'username', 'email', 'first_name', 'last_name', 'language', 'root_admin', '2fa', 'created_at', 'updated_at'],
        ]);

        $this->assertDatabaseHas('users', ['username' => 'new.test.name', 'email' => 'new@emailtest.com']);
        $user = $user->fresh();

        $response->assertJson([
            'object' => 'user',
            'attributes' => $this->getTransformer(UserTransformer::class)->transform($user),
        ]);
    }

    /**
     * Test that a user can be deleted from the database.
     */
    public function testDeleteUser()
    {
        $user = User::factory()->create();
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $response = $this->delete('/api/application/users/' . $user->id);
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /**
     * Test that an API key without write permissions cannot create, update, or
     * delete a user model.
     *
     * @dataProvider userWriteEndpointsDataProvider
     */
    public function testApiKeyWithoutWritePermissions(string $method, string $url)
    {
        $this->createNewDefaultApiKey($this->getApiUser(), ['r_users' => AdminAcl::READ]);

        if (str_contains($url, '{id}')) {
            $user = User::factory()->create();
            $url = str_replace('{id}', $user->id, $url);
        }

        $response = $this->$method($url);
        $this->assertAccessDeniedJson($response);
    }

    /**
     * Endpoints that should return a 403 error when the key does not have write
     * permissions for user management.
     */
    public function userWriteEndpointsDataProvider(): array
    {
        return [
            ['postJson', '/api/application/users'],
            ['patchJson', '/api/application/users/{id}'],
            ['delete', '/api/application/users/{id}'],
        ];
    }
}
