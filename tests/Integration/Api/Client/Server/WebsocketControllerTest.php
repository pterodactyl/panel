<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server;

use Carbon\Carbon;
use Lcobucci\JWT\Parser;
use Carbon\CarbonImmutable;
use Lcobucci\JWT\Signer\Key;
use Illuminate\Http\Response;
use Pterodactyl\Models\Permission;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class WebsocketControllerTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that a subuser attempting to connect to the websocket recieves an error if they
     * do not explicitly have the permission.
     */
    public function testSubuserWithoutWebsocketPermissionReceivesError()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_CONTROL_RESTART]);

        $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/websocket")
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('errors.0.code', 'HttpForbiddenException')
            ->assertJsonPath('errors.0.detail', 'You do not have permission to connect to this server\'s websocket.');
    }

    /**
     * Test that the expected permissions are returned for the server owner and that the JWT is
     * configured correctly.
     */
    public function testJwtAndWebsocketUrlAreReturnedForServerOwner()
    {
        CarbonImmutable::setTestNow(Carbon::now());

        /** @var \Pterodactyl\Models\User $user */
        /** @var \Pterodactyl\Models\Server $server */
        [$user, $server] = $this->generateTestAccount();

        // Force the node to HTTPS since we want to confirm it gets transformed to wss:// in the URL.
        $server->node->scheme = 'https';
        $server->node->save();

        $response = $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/websocket");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['token', 'socket']]);

        $connection = $response->json('data.socket');
        $this->assertStringStartsWith('wss://', $connection, 'Failed asserting that websocket connection address has expected "wss://" prefix.');
        $this->assertStringEndsWith("/api/servers/{$server->uuid}/ws", $connection, 'Failed asserting that websocket connection address uses expected Wings endpoint.');

        $token = (new Parser)->parse($response->json('data.token'));

        $this->assertTrue(
            $token->verify(new Sha256, new Key($server->node->getDecryptedKey())),
            'Failed to validate that the JWT data returned was signed using the Node\'s secret key.'
        );

        // Check that the claims are generated correctly.
        $this->assertSame(config('app.url'), $token->getClaim('iss'));
        $this->assertSame($server->node->getConnectionAddress(), $token->getClaim('aud'));
        $this->assertSame(CarbonImmutable::now()->getTimestamp(), $token->getClaim('iat'));
        $this->assertSame(CarbonImmutable::now()->subMinutes(5)->getTimestamp(), $token->getClaim('nbf'));
        $this->assertSame(CarbonImmutable::now()->addMinutes(10)->getTimestamp(), $token->getClaim('exp'));
        $this->assertSame($user->id, $token->getClaim('user_id'));
        $this->assertSame($server->uuid, $token->getClaim('server_uuid'));
        $this->assertSame(['*'], $token->getClaim('permissions'));
    }

    /**
     * Test that the subuser's permissions are passed along correctly in the generated JWT.
     */
    public function testJwtIsConfiguredCorrectlyForServerSubuser()
    {
        $permissions = [Permission::ACTION_WEBSOCKET_CONNECT, Permission::ACTION_CONTROL_CONSOLE];

        /** @var \Pterodactyl\Models\User $user */
        /** @var \Pterodactyl\Models\Server $server */
        [$user, $server] = $this->generateTestAccount($permissions);

        $response = $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/websocket");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['token', 'socket']]);

        $token = (new Parser)->parse($response->json('data.token'));

        $this->assertTrue(
            $token->verify(new Sha256, new Key($server->node->getDecryptedKey())),
            'Failed to validate that the JWT data returned was signed using the Node\'s secret key.'
        );

        // Check that the claims are generated correctly.
        $this->assertSame($permissions, $token->getClaim('permissions'));
    }
}
