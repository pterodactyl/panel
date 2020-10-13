<?php

namespace Tests\Unit\Services\Nodes;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Node;
use Pterodactyl\Exceptions\DisplayException;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Services\Nodes\NodeDeletionService;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class NodeDeletionServiceTest extends TestCase
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeDeletionService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = m::mock(NodeRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->translator = m::mock(Translator::class);

        $this->service = new NodeDeletionService(
            $this->repository,
            $this->serverRepository,
            $this->translator
        );
    }

    /**
     * Test that a node is deleted if there are no servers attached to it.
     */
    public function testNodeIsDeletedIfNoServersAreAttached()
    {
        $this->serverRepository->shouldReceive('setColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([['node_id', '=', 1]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with(1)->once()->andReturn(1);

        $this->assertEquals(1, $this->service->handle(1));
    }

    /**
     * Test that an exception is thrown if servers are attached to the node.
     */
    public function testExceptionIsThrownIfServersAreAttachedToNode()
    {
        $this->expectException(DisplayException::class);

        $this->serverRepository->shouldReceive('setColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([['node_id', '=', 1]])->once()->andReturn(1);
        $this->translator->shouldReceive('trans')->with('exceptions.node.servers_attached')->once()->andReturnNull();
        $this->repository->shouldNotReceive('delete');

        $this->service->handle(1);
    }

    /**
     * Test that a model can be passed into the handle function rather than an ID.
     */
    public function testModelCanBePassedToFunctionInPlaceOfNodeId()
    {
        $node = factory(Node::class)->make(['id' => 123]);

        $this->serverRepository->shouldReceive('setColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([['node_id', '=', $node->id]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with($node->id)->once()->andReturn(1);

        $this->assertEquals(1, $this->service->handle($node));
    }
}
