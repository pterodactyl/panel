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

use Exception;
use Mockery as m;
use Tests\TestCase;
use Illuminate\Log\Writer;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\Node;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Nodes\UpdateService;
use Pterodactyl\Services\Nodes\CreationService;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface;

class UpdateServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\ConfigurationRepositoryInterface
     */
    protected $configRepository;

    /**
     * @var \GuzzleHttp\Exception\RequestException
     */
    protected $exception;

    /**
     * @var \Pterodactyl\Models\Node
     */
    protected $node;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Nodes\UpdateService
     */
    protected $service;

    /**
     * @var \Illuminate\Log\Writer
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

        $this->service = new UpdateService(
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
        $this->getFunctionMock('\\Pterodactyl\\Service\\Nodes', 'random_bytes')
            ->expects($this->once())->willReturnCallback(function ($bytes) {
                $this->assertEquals(CreationService::DAEMON_SECRET_LENGTH, $bytes);

                return '\00';
            });

        $this->getFunctionMock('\\Pterodactyl\\Service\\Nodes', 'bin2hex')
            ->expects($this->once())->willReturn('hexResponse');

        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->node->id, [
                'name' => 'NewName',
                'daemonSecret' => 'hexResponse',
            ])->andReturn(true);

        $this->configRepository->shouldReceive('setNode')->with($this->node->id)->once()->andReturnSelf()
            ->shouldReceive('setAccessToken')->with($this->node->daemonSecret)->once()->andReturnSelf()
            ->shouldReceive('update')->withNoArgs()->once()->andReturnNull();

        $this->assertTrue($this->service->handle($this->node, ['name' => 'NewName', 'reset_secret' => true]));
    }

    /**
     * Test that daemon secret is not modified when no variable is passed in data.
     */
    public function testNodeIsUpdatedAndDaemonSecretIsNotChanged()
    {
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->node->id, [
                'name' => 'NewName',
            ])->andReturn(true);

        $this->configRepository->shouldReceive('setNode')->with($this->node->id)->once()->andReturnSelf()
            ->shouldReceive('setAccessToken')->with($this->node->daemonSecret)->once()->andReturnSelf()
            ->shouldReceive('update')->withNoArgs()->once()->andReturnNull();

        $this->assertTrue($this->service->handle($this->node, ['name' => 'NewName']));
    }

    /**
     * Test that an exception caused by the daemon is handled properly.
     */
    public function testExceptionCausedByDaemonIsHandled()
    {
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->node->id, [
                'name' => 'NewName',
            ])->andReturn(true);

        $this->configRepository->shouldReceive('setNode')->with($this->node->id)->once()->andThrow($this->exception);
        $this->writer->shouldReceive('warning')->with($this->exception)->once()->andReturnNull();
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('getStatusCode')->withNoArgs()->once()->andReturn(400);

        try {
            $this->service->handle($this->node, ['name' => 'NewName']);
        } catch (Exception $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(
                trans('admin/exceptions.node.daemon_off_config_updated', ['code' => 400]), $exception->getMessage()
            );
        }
    }

    /**
     * Test that an ID can be passed in place of a model.
     */
    public function testFunctionCanAcceptANodeIdInPlaceOfModel()
    {
        $this->repository->shouldReceive('find')->with($this->node->id)->once()->andReturn($this->node);
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($this->node->id, [
                'name' => 'NewName',
            ])->andReturn(true);

        $this->configRepository->shouldReceive('setNode')->with($this->node->id)->once()->andReturnSelf()
            ->shouldReceive('setAccessToken')->with($this->node->daemonSecret)->once()->andReturnSelf()
            ->shouldReceive('update')->withNoArgs()->once()->andReturnNull();

        $this->assertTrue($this->service->handle($this->node->id, ['name' => 'NewName']));
    }
}
