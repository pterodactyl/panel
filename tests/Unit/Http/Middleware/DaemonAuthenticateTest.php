<?php

namespace Tests\Unit\Http\Middleware;

use Mockery as m;
use Pterodactyl\Models\Node;
use Pterodactyl\Http\Middleware\DaemonAuthenticate;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

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
     * Test a valid daemon connection.
     */
    public function testValidDaemonConnection()
    {
        $node = factory(Node::class)->make();

        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.name');
        $this->request->shouldReceive('header')->with('X-Access-Node')->twice()->andReturn($node->uuid);

        $this->repository->shouldReceive('findFirstWhere')->with(['daemonSecret' => $node->uuid])->once()->andReturn($node);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestHasAttribute('node');
        $this->assertRequestAttributeEquals($node, 'node');
    }

    /**
     * Test that ignored routes do not continue through the middleware.
     */
    public function testIgnoredRouteShouldContinue()
    {
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('daemon.configuration');

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestMissingAttribute('node');
    }

    /**
     * Test that a request missing a X-Access-Node header causes an exception.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testExceptionThrownIfMissingHeader()
    {
        $this->request->shouldReceive('route->getName')->withNoArgs()->once()->andReturn('random.name');
        $this->request->shouldReceive('header')->with('X-Access-Node')->once()->andReturn(false);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\DaemonAuthenticate
     */
    private function getMiddleware(): DaemonAuthenticate
    {
        return new DaemonAuthenticate($this->repository);
    }
}
