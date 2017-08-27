<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Tests\Unit\Services\Nodes;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Node;
use Pterodactyl\Services\Nodes\DeletionService;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class DeletionServiceTest extends TestCase
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
     * @var \Pterodactyl\Services\Nodes\DeletionService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(NodeRepositoryInterface::class);
        $this->serverRepository = m::mock(ServerRepositoryInterface::class);
        $this->translator = m::mock(Translator::class);

        $this->service = new DeletionService(
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
        $this->serverRepository->shouldReceive('withColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([['node_id', '=', 1]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with(1)->once()->andReturn(true);

        $this->assertTrue(
            $this->service->handle(1),
            'Assert that deletion returns a positive boolean value.'
        );
    }

    /**
     * Test that an exception is thrown if servers are attached to the node.
     *
     * @expectedException \Pterodactyl\Exceptions\DisplayException
     */
    public function testExceptionIsThrownIfServersAreAttachedToNode()
    {
        $this->serverRepository->shouldReceive('withColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([['node_id', '=', 1]])->once()->andReturn(1);
        $this->translator->shouldReceive('trans')->with('admin/exceptions.node.servers_attached')->once()->andReturnNull();
        $this->repository->shouldNotReceive('delete');

        $this->service->handle(1);
    }

    /**
     * Test that a model can be passed into the handle function rather than an ID.
     */
    public function testModelCanBePassedToFunctionInPlaceOfNodeId()
    {
        $node = factory(Node::class)->make();

        $this->serverRepository->shouldReceive('withColumns')->with('id')->once()->andReturnSelf()
            ->shouldReceive('findCountWhere')->with([['node_id', '=', $node->id]])->once()->andReturn(0);
        $this->repository->shouldReceive('delete')->with($node->id)->once()->andReturn(true);

        $this->assertTrue(
            $this->service->handle($node->id),
            'Assert that deletion returns a positive boolean value.'
        );
    }
}
