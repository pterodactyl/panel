<?php

namespace Tests\Unit\Http\Middleware\Server;

use Mockery as m;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Database;
use Tests\Unit\Http\Middleware\MiddlewareTestCase;
use Pterodactyl\Http\Middleware\Server\DatabaseBelongsToServer;
use Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface;

class DatabaseBelongsToServerTest extends MiddlewareTestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\DatabaseRepositoryInterface|\Mockery\Mock
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(DatabaseRepositoryInterface::class);
    }

    /**
     * Test a successful middleware instance.
     */
    public function testSuccessfulMiddleware()
    {
        $model = factory(Server::class)->make();
        $database = factory(Database::class)->make([
            'server_id' => $model->id,
        ]);
        $this->setRequestAttribute('server', $model);

        $this->request->shouldReceive('input')->with('database')->once()->andReturn($database->id);
        $this->repository->shouldReceive('find')->with($database->id)->once()->andReturn($database);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
        $this->assertRequestHasAttribute('database');
        $this->assertRequestAttributeEquals($database, 'database');
    }

    /**
     * Test that an exception is thrown if no database record is found.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testExceptionIsThrownIfNoDatabaseRecordFound()
    {
        $model = factory(Server::class)->make();
        $database = factory(Database::class)->make();
        $this->setRequestAttribute('server', $model);

        $this->request->shouldReceive('input')->with('database')->once()->andReturn($database->id);
        $this->repository->shouldReceive('find')->with($database->id)->once()->andReturnNull();

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Test that an exception is found if the database server does not match the
     * request server.
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testExceptionIsThrownIfDatabaseServerDoesNotMatchCurrent()
    {
        $model = factory(Server::class)->make();
        $database = factory(Database::class)->make();
        $this->setRequestAttribute('server', $model);

        $this->request->shouldReceive('input')->with('database')->once()->andReturn($database->id);
        $this->repository->shouldReceive('find')->with($database->id)->once()->andReturn($database);

        $this->getMiddleware()->handle($this->request, $this->getClosureAssertions());
    }

    /**
     * Return an instance of the middleware using mocked dependencies.
     *
     * @return \Pterodactyl\Http\Middleware\Server\DatabaseBelongsToServer
     */
    private function getMiddleware(): DatabaseBelongsToServer
    {
        return new DatabaseBelongsToServer($this->repository);
    }
}
