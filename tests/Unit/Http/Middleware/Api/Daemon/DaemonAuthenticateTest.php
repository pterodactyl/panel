<?php

namespace Pterodactyl\Tests\Unit\Http\Middleware\Api\Daemon;

use Mockery as m;
use Mockery\MockInterface;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Location;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Http\Middleware\Api\Daemon\DaemonAuthenticate;
use Pterodactyl\Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DaemonAuthenticateTest extends MiddlewareTestCase
{
    private MockInterface $encrypter;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->encrypter = m::mock(Encrypter::class);
    }

    /**
     * Test that if we are accessing the daemon.configuration route this middleware is not
     * applied in order to allow an unauthenticated request to use a token to grab data.
     */
    public function testResponseShouldContinueIfRouteIsExempted()
    {
        $this->request->expects('route->getName')->withNoArgs()->andReturn('daemon.configuration');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that not passing in the bearer token will result in a HTTP/401 error with the
     * proper response headers.
     */
    public function testResponseShouldFailIfNoTokenIsProvided()
    {
        $this->request->expects('route->getName')->withNoArgs()->andReturn('random.route');
        $this->request->expects('bearerToken')->withNoArgs()->andReturnNull();

        try {
            $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        } catch (HttpException $exception) {
            $this->assertEquals(401, $exception->getStatusCode(), 'Assert that a status code of 401 is returned.');
            $this->assertTrue(is_array($exception->getHeaders()), 'Assert that an array of headers is returned.');
            $this->assertArrayHasKey('WWW-Authenticate', $exception->getHeaders(), 'Assert exception headers contains WWW-Authenticate.');
            $this->assertEquals('Bearer', $exception->getHeaders()['WWW-Authenticate']);
        }
    }

    /**
     * Test that passing in an invalid node daemon secret will result in a bad request
     * exception being returned.
     *
     * @dataProvider badTokenDataProvider
     */
    public function testResponseShouldFailIfTokenFormatIsIncorrect(string $token)
    {
        $this->expectException(BadRequestHttpException::class);

        $this->request->expects('route->getName')->withNoArgs()->andReturn('random.route');
        $this->request->expects('bearerToken')->withNoArgs()->andReturn($token);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an access denied error is returned if the node is valid but the token
     * provided is not valid.
     */
    public function testResponseShouldFailIfTokenIsNotValid()
    {
        $node = Node::factory()
            ->for(Location::factory())
            ->create();

        $this->expectException(AccessDeniedHttpException::class);

        $this->request->expects('route->getName')->withNoArgs()->andReturn('random.route');
        $this->request->expects('bearerToken')->withNoArgs()->andReturn($node->daemon_token_id . '.random_string_123');

        $this->encrypter->expects('decrypt')->with($node->daemon_token)->andReturns(decrypt($node->daemon_token));

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an access denied exception is returned if the node is not found using
     * the token ID provided.
     */
    public function testResponseShouldFailIfNodeIsNotFound()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->request->expects('route->getName')->withNoArgs()->andReturn('random.route');
        $this->request->expects('bearerToken')->withNoArgs()->andReturn('abcd1234.random_string_123');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test a successful middleware process.
     */
    public function testSuccessfulMiddlewareProcess()
    {
        $node = Node::factory()
            ->for(Location::factory())
            ->create();
        $node->daemon_token = encrypt('the_same');
        $node->save();

        $this->request->expects('route->getName')->withNoArgs()->andReturn('random.route');
        $this->request->expects('bearerToken')->withNoArgs()->andReturn($node->daemon_token_id . '.the_same');
        $this->encrypter->expects('decrypt')->with($node->daemon_token)->andReturns(decrypt($node->daemon_token));

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestHasAttribute('node');
        $this->assertRequestAttributeEquals($node->fresh(), 'node');
    }

    /**
     * Provides different tokens that should trigger a bad request exception due to
     * their formatting.
     *
     * @return array|\string[][]
     */
    public function badTokenDataProvider(): array
    {
        return [
            ['foo'],
            ['foobar'],
            ['foo-bar'],
            ['foo.bar.baz'],
            ['.foo'],
            ['foo.'],
            ['foo..bar'],
        ];
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     */
    private function getMiddleware(): DaemonAuthenticate
    {
        return new DaemonAuthenticate($this->encrypter);
    }
}
