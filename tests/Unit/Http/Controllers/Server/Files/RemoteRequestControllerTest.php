<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Http\Controllers\Server\Files;

use Mockery as m;
use GuzzleHttp\Psr7\Response;
use Pterodactyl\Models\Server;
use Tests\Traits\MocksRequestException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Config\Repository;
use Pterodactyl\Exceptions\PterodactylException;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;
use Pterodactyl\Http\Controllers\Server\Files\RemoteRequestController;

class RemoteRequestControllerTest extends ControllerTestCase
{
    use MocksRequestException;

    /**
     * @var \Illuminate\Contracts\Config\Repository|\Mockery\Mock
     */
    protected $config;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->config = m::mock(Repository::class);
        $this->repository = m::mock(FileRepositoryInterface::class);
    }

    /**
     * Test the directory listing controller.
     */
    public function testDirectoryController()
    {
        $controller = $this->getController();

        $server = factory(Server::class)->make();
        $this->setRequestAttribute('server', $server);
        $this->setRequestAttribute('server_token', 'abc123');

        $controller->shouldReceive('authorize')->with('list-files', $server)->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('directory', '/')->once()->andReturn('/');
        $this->repository->shouldReceive('setServer')->with($server)->once()->andReturnSelf()
            ->shouldReceive('setToken')->with('abc123')->once()->andReturnSelf()
            ->shouldReceive('getDirectory')->with('/')->once()->andReturn(['folders' => 1, 'files' => 2]);
        $this->config->shouldReceive('get')->with('pterodactyl.files.editable')->once()->andReturn([]);

        $response = $controller->directory($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('server.files.list', $response);
        $this->assertViewHasKey('files', $response);
        $this->assertViewHasKey('folders', $response);
        $this->assertViewHasKey('editableMime', $response);
        $this->assertViewHasKey('directory', $response);
        $this->assertViewKeyEquals('files', 2, $response);
        $this->assertViewKeyEquals('folders', 1, $response);
        $this->assertViewKeyEquals('editableMime', [], $response);
        $this->assertViewKeyEquals('directory.first', false, $response);
        $this->assertViewKeyEquals('directory.header', '', $response);
    }

    /**
     * Test that the controller properly handles an exception thrown by the daemon conneciton.
     */
    public function testExceptionThrownByDaemonConnectionIsHandledByDisplayController()
    {
        $this->configureExceptionMock();
        $controller = $this->getController();

        $server = factory(Server::class)->make();
        $this->setRequestAttribute('server', $server);

        $controller->shouldReceive('authorize')->with('list-files', $server)->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('directory', '/')->once()->andReturn('/');
        $this->repository->shouldReceive('setServer')->with($server)->once()->andThrow($this->getExceptionMock());

        try {
            $controller->directory($this->request);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(DaemonConnectionException::class, $exception);
            $this->assertInstanceOf(RequestException::class, $exception->getPrevious());
        }
    }

    /**
     * Test the store controller.
     */
    public function testStoreController()
    {
        $controller = $this->getController();

        $server = factory(Server::class)->make();
        $this->setRequestAttribute('server', $server);
        $this->setRequestAttribute('server_token', 'abc123');

        $controller->shouldReceive('authorize')->with('save-files', $server)->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('file')->once()->andReturn('file.txt');
        $this->request->shouldReceive('input')->with('contents')->once()->andReturn('file contents');
        $this->repository->shouldReceive('setServer')->with($server)->once()->andReturnSelf()
            ->shouldReceive('setToken')->with('abc123')->once()->andReturnSelf()
            ->shouldReceive('putContent')->with('file.txt', 'file contents')->once()->andReturn(new Response);

        $response = $controller->store($this->request);
        $this->assertIsResponse($response);
        $this->assertResponseCodeEquals(204, $response);
    }

    /**
     * Test that the controller properly handles an exception thrown by the daemon conneciton.
     */
    public function testExceptionThrownByDaemonConnectionIsHandledByStoreController()
    {
        $this->configureExceptionMock();
        $controller = $this->getController();

        $server = factory(Server::class)->make();
        $this->setRequestAttribute('server', $server);

        $controller->shouldReceive('authorize')->with('save-files', $server)->once()->andReturnNull();
        $this->repository->shouldReceive('setServer')->with($server)->once()->andThrow($this->getExceptionMock());

        try {
            $controller->store($this->request);
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(DaemonConnectionException::class, $exception);
            $this->assertInstanceOf(RequestException::class, $exception->getPrevious());
        }
    }

    /**
     * Return a mocked instance of the controller to allow access to authorization functionality.
     *
     * @return \Pterodactyl\Http\Controllers\Server\Files\RemoteRequestController|\Mockery\Mock
     */
    private function getController()
    {
        return $this->buildMockedController(RemoteRequestController::class, [$this->config, $this->repository]);
    }
}
