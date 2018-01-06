<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Nodes;

use Exception;
use Mockery as m;
use Tests\TestCase;
use Illuminate\Log\Writer;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\Node;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Nodes\NodeUpdateService;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;

class NodeUpdateServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface|\Mockery\Mock
     */
    protected $configRepository;

    /**
     * @var \GuzzleHttp\Exception\RequestException|\Mockery\Mock
     */
    protected $exception;

    /**
     * @var \Pterodactyl\Models\Node
     */
    protected $node;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeUpdateService
     */
    protected $service;

    /**
     * @var \Illuminate\Log\Writer|\Mockery\Mock
     */
    protected $writer;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->node = factory(Node::class)->make();

        $this->configRepository = m::mock(ConfigurationRepositoryInterface::class);
        $this->exception = m::mock(RequestException::class);
        $this->repository = m::mock(NodeRepositoryInterface::class);
        $this->writer = m::mock(Writer::class);

        $this->service = new NodeUpdateService(
            $this->configRepository,
            $this->repository,
            $this->writer
        );
    }

    /**
     * Test that the daemon secret is reset when `reset_secret` is passed in the data.
     */
    public function testNodeIsUpdatedAndDaemonSecretIsReset()
    {
        $this->getFunctionMock('\\Pterodactyl\\Services\\Nodes', 'str_random')
            ->expects($this->once())->willReturn('random_string');

        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->node->id, [
                'name' => 'NewName',
                'daemonSecret' => 'random_string',
            ])->andReturn(true);

        $this->configRepository->shouldReceive('setNode')->with($this->node)->once()->andReturnSelf()
            ->shouldReceive('update')->withNoArgs()->once()->andReturn(new Response);

        $this->assertTrue($this->service->handle($this->node, ['name' => 'NewName', 'reset_secret' => true]));
    }

    /**
     * Test that daemon secret is not modified when no variable is passed in data.
     */
    public function testNodeIsUpdatedAndDaemonSecretIsNotChanged()
    {
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->node->id, [
                'name' => 'NewName',
            ])->andReturn(true);

        $this->configRepository->shouldReceive('setNode')->with($this->node)->once()->andReturnSelf()
            ->shouldReceive('update')->withNoArgs()->once()->andReturn(new Response);

        $this->assertTrue($this->service->handle($this->node, ['name' => 'NewName']));
    }

    /**
     * Test that an exception caused by the daemon is handled properly.
     */
    public function testExceptionCausedByDaemonIsHandled()
    {
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->node->id, [
                'name' => 'NewName',
            ])->andReturn(new Response);

        $this->configRepository->shouldReceive('setNode')->with($this->node)->once()->andThrow($this->exception);
        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('getStatusCode')->withNoArgs()->once()->andReturn(400);

        try {
            $this->service->handle($this->node, ['name' => 'NewName']);
        } catch (Exception $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(
                trans('exceptions.node.daemon_off_config_updated', ['code' => 400]),
                $exception->getMessage()
            );
        }
    }

    /**
     * Test that an ID can be passed in place of a model.
     */
    public function testFunctionCanAcceptANodeIdInPlaceOfModel()
    {
        $this->repository->shouldReceive('find')->with($this->node->id)->once()->andReturn($this->node);
        $this->repository->shouldReceive('withoutFreshModel')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->node->id, [
                'name' => 'NewName',
            ])->andReturn(true);

        $this->configRepository->shouldReceive('setNode')->with($this->node)->once()->andReturnSelf()
            ->shouldReceive('update')->withNoArgs()->once()->andReturn(new Response);

        $this->assertTrue($this->service->handle($this->node->id, ['name' => 'NewName']));
    }
}
