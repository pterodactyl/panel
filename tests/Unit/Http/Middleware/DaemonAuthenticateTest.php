<?php

namespace Tests\Unit\Http\Middleware;

use Mockery as m;
use App\Models\Node;
use App\Http\Middleware\DaemonAuthenticate;
use App\Contracts\Repository\NodeRepositoryInterface;

class DaemonAuthenticateTest extends MiddlewareTestCase
{
    /**
     * @var \App\Contracts\Repository\NodeRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = m::mock(NodeRepositoryInterface::class);
    }

    /**
     * Test a valid daemon connection.
     */
    public function testValidDaemonConnection()
    {
        $this->setRequestRouteName('random.name');
        $node = factory(Node::class)->make();

        $this->request->shouldReceive('header')->with('X-Access-Node')->twice()->andReturn($node->daemonSecret);

        $this->repository->shouldReceive('findFirstWhere')->with(['daemonSecret' => $node->daemonSecret])->once()->andReturn($node);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestHasAttribute('node');
        $this->assertRequestAttributeEquals($node, 'node');
    }

    /**
     * Test that ignored routes do not continue through the middleware.
     */
    public function testIgnoredRouteShouldContinue()
    {
        $this->setRequestRouteName('daemon.configuration');

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
        $this->setRequestRouteName('random.name');

        $this->request->shouldReceive('header')->with('X-Access-Node')->once()->andReturn(false);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \App\Http\Middleware\DaemonAuthenticate
     */
    private function getMiddleware(): DaemonAuthenticate
    {
        return new DaemonAuthenticate($this->repository);
    }
}
