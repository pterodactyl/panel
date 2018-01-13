<?php

namespace Tests\Unit\Http\Middleware\Api\Daemon;

use Mockery as m;
use Pterodactyl\Models\Node;
use Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Http\Middleware\Api\Daemon\DaemonAuthenticate;

class DaemonAuthenticateTest extends MiddlewareTestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(NodeRepositoryInterface::class);
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
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testResponseShouldFailIfNoNodeIsFound()
    {
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.route');
        $this->request->shouldReceive('bearerToken')->withNoArgs()->once()->andReturn('test1234');

        $this->repository->shouldReceive('findFirstWhere')->with([['daemonSecret', '=', 'test1234']])->once()->andThrow(new RecordNotFoundException);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
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
        $this->assertRequestHasAttribute('node');
        $this->assertRequestAttributeEquals($model, 'node');
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\Api\Daemon\DaemonAuthenticate
     */
    private function getMiddleware(): DaemonAuthenticate
    {
        return new DaemonAuthenticate($this->repository);
    }
}
