<?php

namespace Tests\Unit\Http\Middleware\Daemon;

use Closure;
use Mockery as m;
use Tests\TestCase;
use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Http\Middleware\Daemon\DaemonAuthenticate;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;

class DaemonAuthenticateTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \Illuminate\Http\Request|\Mockery\Mock
     */
    private $request;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(NodeRepositoryInterface::class);
        $this->request = m::mock(Request::class);
        $this->request->attributes = new ParameterBag();
    }

    /**
     * Test that if we are accessing the daemon.configuration route this middleware is not
     * applied in order to allow an unauthenticated request to use a token to grab data.
     */
    public function testResponseShouldContinueIfRouteIsExempted()
    {
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('daemon.configuration');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that not passing in the bearer token will result in a HTTP/401 error with the
     * proper response headers.
     */
    public function testResponseShouldFailIfNoTokenIsProvided()
    {
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.route');
        $this->request->shouldReceive('bearerToken')->withNoArgs()->once()->andReturnNull();

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
     * Test that passing in an invalid node daemon secret will result in a HTTP/403
     * error response.
     */
    public function testResponseShouldFailIfNoNodeIsFound()
    {
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.route');
        $this->request->shouldReceive('bearerToken')->withNoArgs()->once()->andReturn('test1234');

        $this->repository->shouldReceive('findFirstWhere')->with([['daemonSecret', '=', 'test1234']])->once()->andThrow(new RecordNotFoundException);

        try {
            $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        } catch (HttpException $exception) {
            $this->assertEquals(403, $exception->getStatusCode(), 'Assert that a status code of 403 is returned.');
        }
    }

    /**
     * Test a successful middleware process.
     */
    public function testSuccessfulMiddlewareProcess()
    {
        $model = factory(Node::class)->make();

        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.route');
        $this->request->shouldReceive('bearerToken')->withNoArgs()->once()->andReturn($model->daemonSecret);

        $this->repository->shouldReceive('findFirstWhere')->with([['daemonSecret', '=', $model->daemonSecret]])->once()->andReturn($model);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertTrue($this->request->attributes->has('node'), 'Assert request attributes contains node.');
        $this->assertSame($model, $this->request->attributes->get('node'));
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\Daemon\DaemonAuthenticate
     */
    private function getMiddleware(): DaemonAuthenticate
    {
        return new DaemonAuthenticate($this->repository);
    }

    /**
     * Provide a closure to be used when validating that the response from the middleware
     * is the same request object we passed into it.
     */
    private function getClosureAssertions(): Closure
    {
        return function ($response) {
            $this->assertInstanceOf(Request::class, $response);
            $this->assertSame($this->request, $response);
        };
    }
}
