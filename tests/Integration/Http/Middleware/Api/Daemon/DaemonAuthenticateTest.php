<?php

declare(strict_types=1);

namespace Pterodactyl\Tests\Pest\Integration\Http\Middleware\Api\Daemon\DaemonAuthenticateTest;

use Pterodactyl\Http\Middleware\Api\Daemon\DaemonAuthenticate;
use Pterodactyl\Models\Node;
use Pterodactyl\Tests\Assertions\MiddlewareAttributeAssertionsTrait;
use Pterodactyl\Tests\Integration\IntegrationTestCase;
use Pterodactyl\Tests\Traits\Http\MocksMiddlewareClosure;
use Pterodactyl\Tests\Traits\Http\RequestMockHelpers;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(IntegrationTestCase::class, RequestMockHelpers::class, MocksMiddlewareClosure::class, MiddlewareAttributeAssertionsTrait::class);
beforeEach(function () {
    $this->buildRequestMock();
});
/** Tokens that should trigger a bad request exception due to their formatting. */
dataset('badTokenDataProvider', function () {
    return [['foo'], ['foobar'], ['foo-bar'], ['foo.bar.baz'], ['.foo'], ['foo.'], ['foo..bar']];
});
test('response should continue if route is exempted', function () {
    $this->setRequestRouteName('daemon.configuration');
    $nextCalled = false;
    getMiddleware()->handle($this->request, function ($response) use (&$nextCalled) {
        $nextCalled = true;
        ($this->getClosureAssertions())($response);
    });
    expect($nextCalled)->toBeTrue();
});
test('response should fail if no token is provided', function () {
    $this->setRequestRouteName('random.route');
    $this->setRequestBearerToken(null);
    try {
        getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->fail('Expected an HttpException.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toEqual(401);
        expect($exception->getHeaders())->toHaveKey('WWW-Authenticate');
        expect($exception->getHeaders()['WWW-Authenticate'])->toEqual('Bearer');
    }
});
test('response should fail if token format is incorrect', function (string $token) {
    $this->expectException(BadRequestHttpException::class);
    $this->setRequestRouteName('random.route');
    $this->setRequestBearerToken($token);
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
})->with('badTokenDataProvider');
test('response should fail if token is not valid', function () {
    $this->expectException(AccessDeniedHttpException::class);
    $node = Node::factory()->withLocation()->create();
    $this->setRequestRouteName('random.route');
    $this->setRequestBearerToken($node->daemon_token_id.'.random_string_123');
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
});
test('response should fail if node is not found', function () {
    $this->expectException(AccessDeniedHttpException::class);
    $this->setRequestRouteName('random.route');
    $this->setRequestBearerToken('abcd1234.random_string_123');
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
});
test('successful middleware process', function () {
    $node = Node::factory()->withLocation()->create();
    $this->setRequestRouteName('random.route');
    $this->setRequestBearerToken($node->daemon_token_id.'.'.decrypt($node->daemon_token));
    getMiddleware()->handle($this->request, $this->getClosureAssertions());
    $this->assertRequestHasAttribute('node');
    expect($this->request->attributes->get('node'))->toBeInstanceOf(Node::class);
    expect($this->request->attributes->get('node')->is($node))->toBeTrue();
});
/** Resolve the middleware with the real encrypter. */
function getMiddleware(): DaemonAuthenticate
{
    return app(DaemonAuthenticate::class);
}
