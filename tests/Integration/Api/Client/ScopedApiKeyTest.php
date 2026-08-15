<?php

namespace Pterodactyl\Tests\Integration\Api\Client;

use Pterodactyl\Models\User;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Permission;

class ScopedApiKeyTest extends ClientApiIntegrationTestCase
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
     * Builds an authorization header value for the given key and plain-text secret.
     */
    private function bearer(ApiKey $key): string
    {
        return 'Bearer ' . $key->identifier . decrypt($key->token);
    }

    /**
     * A key without any scoping behaves exactly as before: full access to every
     * server and action available to the owning user.
     */
    public function testUnscopedKeyRetainsFullAccess()
    {
        [$user, $server] = $this->generateTestAccount();

        $token = $user->createToken('test', null);

        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->getJson("/api/client/servers/$server->uuid")
            ->assertOk();
    }

    /**
     * A key scoped to a different server cannot see or interact with a server
     * outside of its scope, even though the owning user has full access.
     */
    public function testKeyScopedToOtherServerCannotAccessServer()
    {
        [$user, $server] = $this->generateTestAccount();

        $token = $user->createToken('test', null, null, ['00000000-0000-0000-0000-000000000000']);

        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->getJson("/api/client/servers/$server->uuid")
            ->assertNotFound();
    }

    /**
     * A key scoped to the server itself can access it.
     */
    public function testKeyScopedToServerCanAccessServer()
    {
        [$user, $server] = $this->generateTestAccount();

        $token = $user->createToken('test', null, null, [$server->uuid]);

        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->getJson("/api/client/servers/$server->uuid")
            ->assertOk();
    }

    /**
     * A key restricted to a permission set is denied actions outside that set
     * even when the owning user is the server owner.
     */
    public function testKeyPermissionRestrictionsAreEnforced()
    {
        [$user, $server] = $this->generateTestAccount();

        $token = $user->createToken('test', null, [Permission::ACTION_FILE_READ], null);

        // Denied: power control is not within the key's permission set.
        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->postJson("/api/client/servers/$server->uuid/power", ['signal' => 'start'])
            ->assertForbidden();
    }

    /**
     * The server listing endpoint only returns servers within the key's scope.
     */
    public function testServerListingIsFilteredForScopedKeys()
    {
        [$user, $server] = $this->generateTestAccount();

        $scoped = $user->createToken('test', null, null, ['00000000-0000-0000-0000-000000000000']);

        $this->withHeader('Authorization', $this->bearer($scoped->accessToken))
            ->getJson('/api/client')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 0);
    }

    /**
     * A restricted key may not create additional API keys — that would allow it
     * to escalate itself into an unrestricted key.
     */
    public function testRestrictedKeyCannotCreateNewKeys()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $token = $user->createToken('test', null, [Permission::ACTION_FILE_READ], null);

        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->postJson('/api/client/account/api-keys', [
                'description' => 'Sneaky unrestricted key',
            ])
            ->assertForbidden();
    }

    /**
     * A restricted key may not register SSH keys — SSH keys grant SFTP access to
     * every server the user owns, which would bypass the key's scoping entirely.
     */
    public function testRestrictedKeyCannotManageSshKeys()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $token = $user->createToken('test', null, [Permission::ACTION_FILE_READ], null);

        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->postJson('/api/client/account/ssh-keys', [
                'name' => 'escalation',
                'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOMe9UEpn7kXA1cV8pFsWNRDHqNMhh0nJIZzcM9uYRWG',
            ])
            ->assertForbidden();
    }

    /**
     * A restricted key is denied all account level endpoints, including reads.
     */
    public function testRestrictedKeyCannotAccessAccountEndpoints()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $token = $user->createToken('test', null, null, ['00000000-0000-0000-0000-000000000000']);

        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->getJson('/api/client/account')
            ->assertForbidden();
    }

    /**
     * An unscoped key retains access to account endpoints, preserving existing
     * behavior for keys created before scoping existed.
     */
    public function testUnscopedKeyRetainsAccountAccess()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $token = $user->createToken('test', null);

        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->getJson('/api/client/account')
            ->assertOk();
    }

    /**
     * A restricted key belonging to a root admin may not access the application
     * API — that API exposes every server and user on the system and would
     * bypass the key's scoping entirely.
     */
    public function testRestrictedAdminKeyCannotAccessApplicationApi()
    {
        /** @var User $user */
        $user = User::factory()->create(['root_admin' => true]);

        $token = $user->createToken('test', null, [Permission::ACTION_FILE_READ], null);

        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->getJson('/api/application/users')
            ->assertForbidden();
    }

    /**
     * An unrestricted client key belonging to a root admin retains application
     * API access, preserving existing behavior.
     */
    public function testUnrestrictedAdminKeyRetainsApplicationApiAccess()
    {
        /** @var User $user */
        $user = User::factory()->create(['root_admin' => true]);

        $token = $user->createToken('test', null);

        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->getJson('/api/application/users')
            ->assertOk();
    }

    /**
     * The websocket JWT issued to a permission-scoped key must carry the key's
     * permission set — not the owner's wildcard grant — since the daemon
     * enforces permissions from the JWT alone.
     */
    public function testWebsocketJwtPermissionsAreLimitedToKeyScope()
    {
        [$user, $server] = $this->generateTestAccount();

        $token = $user->createToken('test', null, [Permission::ACTION_WEBSOCKET_CONNECT], null);

        $response = $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->getJson("/api/client/servers/$server->uuid/websocket")
            ->assertOk();

        $payload = json_decode(base64_decode(strtr(explode('.', $response->json('data.token'))[1], '-_', '+/')), true);

        $this->assertSame([Permission::ACTION_WEBSOCKET_CONNECT], $payload['permissions']);
    }

    /**
     * A permission-scoped key may not assign subuser permissions beyond its own
     * scope, even when the owner could. This closes a subuser-based escalation.
     */
    public function testScopedKeyCannotAssignSubuserPermissionsBeyondScope()
    {
        [$user, $server] = $this->generateTestAccount();

        $token = $user->createToken('test', null, [
            Permission::ACTION_USER_CREATE,
            Permission::ACTION_USER_READ,
        ], null);

        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->postJson("/api/client/servers/$server->uuid/users", [
                'email' => 'subuser@example.com',
                'permissions' => [Permission::ACTION_CONTROL_CONSOLE, Permission::ACTION_USER_READ],
            ])
            ->assertForbidden();
    }

    /**
     * A restricted admin key may not use the admin enumeration modes to list
     * every server on the panel.
     */
    public function testRestrictedAdminKeyCannotEnumerateAllServers()
    {
        /** @var User $admin */
        $admin = User::factory()->create(['root_admin' => true]);
        [, $otherServer] = $this->generateTestAccount();

        $token = $admin->createToken('test', null, [Permission::ACTION_WEBSOCKET_CONNECT], null);

        $response = $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->getJson('/api/client?type=admin-all')
            ->assertOk();

        $uuids = array_column(array_column($response->json('data'), 'attributes'), 'uuid');
        $this->assertNotContains($otherServer->uuid, $uuids);
    }

    /**
     * The user_permissions meta reported for a permission-scoped key is clamped
     * to the key's scope rather than reporting the owner's wildcard.
     */
    public function testServerMetaPermissionsAreClampedForScopedKey()
    {
        [$user, $server] = $this->generateTestAccount();

        $token = $user->createToken('test', null, [Permission::ACTION_FILE_READ], null);

        $this->withHeader('Authorization', $this->bearer($token->accessToken))
            ->getJson("/api/client/servers/$server->uuid")
            ->assertOk()
            ->assertJsonPath('meta.user_permissions', [Permission::ACTION_FILE_READ]);
    }

    /**
     * A key cannot be created scoped to a server the user has no access to.
     */
    public function testKeyCannotBeScopedToInaccessibleServer()
    {
        [$user] = $this->generateTestAccount();
        [, $otherServer] = $this->generateTestAccount();

        $this->actingAs($user)
            ->postJson('/api/client/account/api-keys', [
                'description' => 'Test Description',
                'allowed_servers' => [$otherServer->uuid],
            ])
            ->assertUnprocessable();
    }
}
