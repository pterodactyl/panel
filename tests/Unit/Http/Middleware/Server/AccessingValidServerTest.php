<?php

namespace Pterodactyl\Tests\Unit\Http\Middleware\Server;

use Mockery as m;
use Pterodactyl\Models\Server;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Pterodactyl\Http\Middleware\Server\AccessingValidServer;
use Pterodactyl\Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
     * @var \Illuminate\Contracts\Routing\ResponseFactory|\Mockery\Mock
     */
    private $response;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->repository = m::mock(ServerRepositoryInterface::class);
        $this->response = m::mock(ResponseFactory::class);
    }

    /**
     * Test that an exception is thrown if the request is an API request and the server is suspended.
     */
    public function testExceptionIsThrownIfServerIsSuspended()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Server is suspended and cannot be accessed.');

        $model = Server::factory()->make(['suspended' => 1]);

        $this->request->shouldReceive('route->parameter')->with('server')->once()->andReturn('123456');
        $this->request->shouldReceive('expectsJson')->withNoArgs()->once()->andReturn(true);

        $this->repository->shouldReceive('getByUuid')->with('123456')->once()->andReturn($model);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an exception is thrown if the request is an API request and the server is not installed.
     */
    public function testExceptionIsThrownIfServerIsNotInstalled()
    {
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Server is still completing the installation process.');

        $model = Server::factory()->make(['installed' => 0]);

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
    public function testCorrectErrorPagesAreRendered(Server $model, string $page, int $httpCode)
    {
        $this->request->shouldReceive('route->parameter')->with('server')->once()->andReturn('123456');
        $this->request->shouldReceive('expectsJson')->withNoArgs()->once()->andReturn(false);
        $this->config->shouldReceive('get')->with('pterodactyl.json_routes', [])->once()->andReturn([]);
        $this->request->shouldReceive('is')->with(...[])->once()->andReturn(false);

        $this->repository->shouldReceive('getByUuid')->with('123456')->once()->andReturn($model);
        $this->response->shouldReceive('view')->with($page, [], $httpCode)->once()->andReturn(true);

        $response = $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertTrue($response);
    }

    /**
     * Test that the full middleware works correctly.
     */
    public function testValidServerProcess()
    {
        $model = Server::factory()->make();

        $this->request->shouldReceive('route->parameter')->with('server')->once()->andReturn('123456');
        $this->request->shouldReceive('expectsJson')->withNoArgs()->once()->andReturn(false);
        $this->config->shouldReceive('get')->with('pterodactyl.json_routes', [])->once()->andReturn([]);
        $this->request->shouldReceive('is')->with(...[])->once()->andReturn(false);

        $this->repository->shouldReceive('getByUuid')->with('123456')->once()->andReturn($model);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestHasAttribute('server');
        $this->assertRequestAttributeEquals($model, 'server');
    }

    /**
     * Provide test data that checks that the correct view is returned for each model type.
     */
    public function viewDataProvider(): array
    {
        // Without this we are unable to instantiate the factory builders for some reason.
        $this->refreshApplication();

        return [
            [Server::factory()->make(['suspended' => 1]), 'errors.suspended', 403],
            [Server::factory()->make(['installed' => 0]), 'errors.installing', 409],
            [Server::factory()->make(['installed' => 2]), 'errors.installing', 409],
        ];
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     */
    private function getMiddleware(): AccessingValidServer
    {
        return new AccessingValidServer($this->config, $this->response, $this->repository);
    }
}
