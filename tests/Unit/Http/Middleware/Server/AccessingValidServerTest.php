<?php

namespace Tests\Unit\Http\Middleware\Server;

use Mockery as m;
use Illuminate\Http\Response;
use Pterodactyl\Models\Server;
use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\Config\Repository;
use Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Http\Middleware\AccessingValidServer;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class AccessingValidServerTest extends MiddlewareTestCase
{
    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    private $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * @var \Illuminate\Contracts\Session\Session|\Mockery\Mock
     */
    private $session;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->session = m::mock(Session::class);
    }

    /**
     * Test that an exception is thrown if the request is an API request and no server is found.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage The requested server was not found on the system.
     */
    public function testExceptionIsThrownIfNoServerIsFoundAndIsAPIRequest()
    {
        $this->request->shouldReceive('route->parameter')->with('server')->once()->andReturn('123456');
        $this->request->shouldReceive('expectsJson')->withNoArgs()->once()->andReturn(true);

        $this->repository->shouldReceive('getByUuid')->with('123456')->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an exception is thrown if the request is an API request and the server is suspended.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @expectedExceptionMessage Server is suspended.
     */
    public function testExceptionIsThrownIfServerIsSuspended()
    {
        $model = factory(Server::class)->make(['suspended' => 1]);

        $this->request->shouldReceive('route->parameter')->with('server')->once()->andReturn('123456');
        $this->request->shouldReceive('expectsJson')->withNoArgs()->once()->andReturn(true);

        $this->repository->shouldReceive('getByUuid')->with('123456')->once()->andReturn($model);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an exception is thrown if the request is an API request and the server is not installed.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @expectedExceptionMessage Server is not marked as installed.
     */
    public function testExceptionIsThrownIfServerIsNotInstalled()
    {
        $model = factory(Server::class)->make(['installed' => 0]);

        $this->request->shouldReceive('route->parameter')->with('server')->once()->andReturn('123456');
        $this->request->shouldReceive('expectsJson')->withNoArgs()->once()->andReturn(true);

        $this->repository->shouldReceive('getByUuid')->with('123456')->once()->andReturn($model);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that the correct error pages are rendered depending on the status of the server.
     *
     * @dataProvider viewDataProvider
     */
    public function testCorrectErrorPagesAreRendered(Server $model = null, string $page, int $httpCode)
    {
        $this->request->shouldReceive('route->parameter')->with('server')->once()->andReturn('123456');
        $this->request->shouldReceive('expectsJson')->withNoArgs()->once()->andReturn(false);
        $this->config->shouldReceive('get')->with('pterodactyl.json_routes', [])->once()->andReturn([]);
        $this->request->shouldReceive('is')->with(...[])->once()->andReturn(false);

        $this->repository->shouldReceive('getByUuid')->with('123456')->once()->andReturn($model);

        $response = $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($page, $response->getOriginalContent()->getName(), 'Assert that the correct view is returned.');
        $this->assertEquals($httpCode, $response->getStatusCode(), 'Assert that the correct HTTP code is returned.');
    }

    /**
     * Test that the full middleware works correctly.
     */
    public function testValidServerProcess()
    {
        $model = factory(Server::class)->make();

        $this->request->shouldReceive('route->parameter')->with('server')->once()->andReturn('123456');
        $this->request->shouldReceive('expectsJson')->withNoArgs()->once()->andReturn(false);
        $this->config->shouldReceive('get')->with('pterodactyl.json_routes', [])->once()->andReturn([]);
        $this->request->shouldReceive('is')->with(...[])->once()->andReturn(false);

        $this->repository->shouldReceive('getByUuid')->with('123456')->once()->andReturn($model);
        $this->session->shouldReceive('now')->with('server_data.model', $model)->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestHasAttribute('server');
        $this->assertRequestAttributeEquals($model, 'server');
    }

    /**
     * Provide test data that checks that the correct view is returned for each model type.
     *
     * @return array
     */
    public function viewDataProvider(): array
    {
        // Without this we are unable to instantiate the factory builders for some reason.
        $this->refreshApplication();

        return [
            [null, 'errors.404', 404],
            [factory(Server::class)->make(['suspended' => 1]), 'errors.suspended', 403],
            [factory(Server::class)->make(['installed' => 0]), 'errors.installing', 403],
            [factory(Server::class)->make(['installed' => 2]), 'errors.installing', 403],
        ];
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\AccessingValidServer
     */
    private function getMiddleware(): AccessingValidServer
    {
        return new AccessingValidServer($this->config, $this->repository, $this->session);
    }
}
