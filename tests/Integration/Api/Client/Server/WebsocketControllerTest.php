<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server;

use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Lcobucci\JWT\Configuration;
use Pterodactyl\Models\Permission;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class WebsocketControllerTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that a subuser attempting to connect to the websocket receives an error if they
     * do not explicitly have the permission.
     */
    public function testSubuserWithoutWebsocketPermissionReceivesError()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_CONTROL_RESTART]);

        $this->actingAs($user)->getJson("/api/client/servers/$server->uuid/websocket")
            ->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('errors.0.code', 'HttpForbiddenException')
            ->assertJsonPath('errors.0.detail', 'You do not have permission to connect to this server\'s websocket.');
    }

    /**
     * Confirm users cannot access the websocket for another user's server.
     */
    public function testUserWithoutPermissionForServerReceivesError()
    {
        [, $server] = $this->generateTestAccount([Permission::ACTION_WEBSOCKET_CONNECT]);
        [$user] = $this->generateTestAccount([Permission::ACTION_WEBSOCKET_CONNECT]);

        $this->actingAs($user)->getJson("/api/client/servers/$server->uuid/websocket")
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test that the expected permissions are returned for the server owner and that the JWT is
     * configured correctly.
     */
    public function testJwtAndWebsocketUrlAreReturnedForServerOwner()
    {
        /** @var \Pterodactyl\Models\User $user */
        /** @var \Pterodactyl\Models\Server $server */
        [$user, $server] = $this->generateTestAccount();

        // Force the node to HTTPS since we want to confirm it gets transformed to wss:// in the URL.
        $server->node->scheme = 'https';
        $server->node->save();

        $response = $this->actingAs($user)->getJson("/api/client/servers/$server->uuid/websocket");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['token', 'socket']]);

        $connection = $response->json('data.socket');
        $this->assertStringStartsWith('wss://', $connection, 'Failed asserting that websocket connection address has expected "wss://" prefix.');
        $this->assertStringEndsWith("/api/servers/$server->uuid/ws", $connection, 'Failed asserting that websocket connection address uses expected Wings endpoint.');

        $config = Configuration::forSymmetricSigner(new Sha256(), $key = InMemory::plainText($server->node->getDecryptedKey()));
        $config->setValidationConstraints(new SignedWith(new Sha256(), $key));
        /** @var \Lcobucci\JWT\Token\Plain $token */
        $token = $config->parser()->parse($response->json('data.token'));

        $this->assertTrue(
            $config->validator()->validate($token, ...$config->validationConstraints()),
            'Failed to validate that the JWT data returned was signed using the Node\'s secret key.'
        );

        // The way we generate times for the JWT will truncate the microseconds from the
        // time, but CarbonImmutable::now() will include them, thus causing test failures.
        //
        // This little chunk of logic just strips those out by generating a new CarbonImmutable
        // instance from the current timestamp, which is how the JWT works. We also need to
        // switch to UTC here for consistency.
        $expect = CarbonImmutable::createFromTimestamp(CarbonImmutable::now()->getTimestamp())->timezone('UTC');

        // Check that the claims are generated correctly.
        $this->assertTrue($token->hasBeenIssuedBy(config('app.url')));
        $this->assertTrue($token->isPermittedFor($server->node->getConnectionAddress()));
        $this->assertEquals($expect, $token->claims()->get('iat'));
        $this->assertEquals($expect->subMinutes(5), $token->claims()->get('nbf'));
        $this->assertEquals($expect->addMinutes(10), $token->claims()->get('exp'));
        $this->assertSame($user->id, $token->claims()->get('user_id'));
        $this->assertSame($server->uuid, $token->claims()->get('server_uuid'));
        $this->assertSame(['*'], $token->claims()->get('permissions'));
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

        $response = $this->actingAs($user)->getJson("/api/client/servers/$server->uuid/websocket");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['token', 'socket']]);

        $config = Configuration::forSymmetricSigner(new Sha256(), $key = InMemory::plainText($server->node->getDecryptedKey()));
        $config->setValidationConstraints(new SignedWith(new Sha256(), $key));
        /** @var \Lcobucci\JWT\Token\Plain $token */
        $token = $config->parser()->parse($response->json('data.token'));

        $this->assertTrue(
            $config->validator()->validate($token, ...$config->validationConstraints()),
            'Failed to validate that the JWT data returned was signed using the Node\'s secret key.'
        );

        // Check that the claims are generated correctly.
        $this->assertSame($permissions, $token->claims()->get('permissions'));
    }
}
