<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Api\Client\Server\WebsocketControllerTest;

use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Pterodactyl\Enum\JwtScope;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

uses(ClientApiIntegrationTestCase::class);
test('subuser without websocket permission receives error', function () {
    [$user, $server] = $this->generateTestAccount([Permissions::ControlRestart->value]);
    $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/websocket")->assertStatus(Response::HTTP_FORBIDDEN)->assertJsonPath('errors.0.code', 'HttpForbiddenException')->assertJsonPath('errors.0.detail', 'You do not have permission to connect to this server\'s websocket.');
});
test('user without permission for server receives error', function () {
    [, $server] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    [$user] = $this->generateTestAccount([Permissions::WebsocketConnect->value]);
    $this->actingAs($user)->getJson("/api/client/servers/{$server->uuid}/websocket")->assertStatus(Response::HTTP_NOT_FOUND);
});
test('jwt and websocket url are returned for server owner', function () {
    /** @var User $user */
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount();
    // Force the node to HTTPS since we want to confirm it gets transformed to wss:// in the URL.
    $server->node->scheme = 'https';
    $server->node->save();
    $response = $this->actingAs($user)->withoutExceptionHandling()->getJson("/api/client/servers/{$server->uuid}/websocket")->assertOk()->assertJsonStructure(['data' => ['token', 'socket']]);
    $connection = $response->json('data.socket');
    expect($connection)->toStartWith('wss://', 'Failed asserting that websocket connection address has expected "wss://" prefix.');
    expect($connection)->toEndWith("/api/servers/{$server->uuid}/ws", 'Failed asserting that websocket connection address uses expected Wings endpoint.');
    $config = Configuration::forSymmetricSigner(new Sha256(), $key = InMemory::plainText($server->node->getDecryptedKey()));
    $config = $config->withValidationConstraints(new SignedWith(new Sha256(), $key));
    /** @var Plain $token */
    $token = $config->parser()->parse($response->json('data.token'));
    expect($config->validator()->validate($token, ...$config->validationConstraints()))->toBeTrue('Failed to validate that the JWT data returned was signed using the Node\'s secret key.');
    // The way we generate times for the JWT will truncate the microseconds from the
    // time, but CarbonImmutable::now() will include them, thus causing test failures.
    //
    // This little chunk of logic just strips those out by generating a new CarbonImmutable
    // instance from the current timestamp, which is how the JWT works. We also need to
    // switch to UTC here for consistency.
    $expect = CarbonImmutable::createFromTimestamp(CarbonImmutable::now()->getTimestamp())->timezone('UTC');
    // Check that the claims are generated correctly.
    expect($token->hasBeenIssuedBy(config('app.url')))->toBeTrue();
    expect($token->isPermittedFor($server->node->getConnectionAddress()))->toBeTrue();
    expect($token->claims()->get('iat'))->toEqual($expect);
    expect($token->claims()->get('nbf'))->toEqual($expect->subMinutes(5));
    expect($token->claims()->get('exp'))->toEqual($expect->addMinutes(10));
    expect($token->claims()->get('user_uuid'))->toBe($user->uuid);
    expect($token->claims()->get('server_uuid'))->toBe($server->uuid);
    expect($token->claims()->get('permissions'))->toBe(['*']);
    expect($token->claims()->get('scope'))->toEqual(JwtScope::Websocket->value);
});
test('jwt is configured correctly for server subuser', function () {
    $permissions = [Permissions::WebsocketConnect->value, Permissions::ControlConsole->value];
    /** @var User $user */
    /** @var Server $server */
    [$user, $server] = $this->generateTestAccount($permissions);
    $response = $this->actingAs($user)->withoutExceptionHandling()->getJson("/api/client/servers/{$server->uuid}/websocket")->assertOk()->assertJsonStructure(['data' => ['token', 'socket']]);
    $config = Configuration::forSymmetricSigner(new Sha256(), $key = InMemory::plainText($server->node->getDecryptedKey()));
    $config = $config->withValidationConstraints(new SignedWith(new Sha256(), $key));
    /** @var Plain $token */
    $token = $config->parser()->parse($response->json('data.token'));
    expect($config->validator()->validate($token, ...$config->validationConstraints()))->toBeTrue('Failed to validate that the JWT data returned was signed using the Node\'s secret key.');
    // Check that the claims are generated correctly.
    expect($token->claims()->get('permissions'))->toBe($permissions);
    expect($token->claims()->get('scope'))->toEqual(JwtScope::Websocket->value);
});
