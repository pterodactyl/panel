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
use Tests\TestCase;
use Illuminate\Log\Writer;
use Illuminate\Http\Request;
use Pterodactyl\Models\Server;
use Illuminate\Contracts\Session\Session;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Config\Repository;
use Tests\Assertions\ControllerAssertionsTrait;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;
use Pterodactyl\Http\Controllers\Server\Files\RemoteRequestController;

class RemoteRequestControllerTest extends TestCase
{
    use ControllerAssertionsTrait;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * @var \Pterodactyl\Http\Controllers\Server\Files\RemoteRequestController
     */
    protected $controller;

    /**
     * @var \Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface
     */
    protected $fileRepository;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

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

        $this->config = m::mock(Repository::class);
        $this->fileRepository = m::mock(FileRepositoryInterface::class);
        $this->request = m::mock(Request::class);
        $this->session = m::mock(Session::class);
        $this->writer = m::mock(Writer::class);

        $this->controller = m::mock(RemoteRequestController::class, [
            $this->config,
            $this->fileRepository,
            $this->session,
            $this->writer,
        ])->makePartial();
    }

    /**
     * Test the directory listing controller.
     */
    public function testDirectoryController()
    {
        $server = factory(Server::class)->make();

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->controller->shouldReceive('authorize')->with('list-files', $server)->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('directory', '/')->once()->andReturn('/');
        $this->session->shouldReceive('get')->with('server_data.token')->once()->andReturn($server->daemonSecret);
        $this->fileRepository->shouldReceive('setNode')->with($server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($server->uuid)->once()->andReturnSelf()
            ->shouldReceive('setAccessToken')->with($server->daemonSecret)->once()->andReturnSelf()
            ->shouldReceive('getDirectory')->with('/')->once()->andReturn(['folders' => 1, 'files' => 2]);
        $this->config->shouldReceive('get')->with('pterodactyl.files.editable')->once()->andReturn([]);

        $response = $this->controller->directory($this->request);
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
        $server = factory(Server::class)->make();
        $exception = m::mock(RequestException::class);

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->controller->shouldReceive('authorize')->with('list-files', $server)->once()->andReturnNull();
        $this->request->shouldReceive('input')->with('directory', '/')->once()->andReturn('/');
        $this->fileRepository->shouldReceive('setNode')->with($server->node_id)->once()->andThrow($exception);

        $this->writer->shouldReceive('warning')->with($exception)->once()->andReturnNull();
        $exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnNull();

        $response = $this->controller->directory($this->request);
        $this->assertIsJsonResponse($response);
        $this->assertResponseJsonEquals(['error' => trans('exceptions.daemon_connection_failed', ['code' => 'E_CONN_REFUSED'])], $response);
        $this->assertResponseCodeEquals(500, $response);
    }

    /**
     * Test the store controller.
     */
    public function testStoreController()
    {
        $server = factory(Server::class)->make();

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->controller->shouldReceive('authorize')->with('save-files', $server)->once()->andReturnNull();
        $this->session->shouldReceive('get')->with('server_data.token')->once()->andReturn($server->daemonSecret);
        $this->request->shouldReceive('input')->with('file')->once()->andReturn('file.txt');
        $this->request->shouldReceive('input')->with('contents')->once()->andReturn('file contents');
        $this->fileRepository->shouldReceive('setNode')->with($server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($server->uuid)->once()->andReturnSelf()
            ->shouldReceive('setAccessToken')->with($server->daemonSecret)->once()->andReturnSelf()
            ->shouldReceive('putContent')->with('file.txt', 'file contents')->once()->andReturnNull();

        $response = $this->controller->store($this->request, '1234');
        $this->assertIsResponse($response);
        $this->assertResponseCodeEquals(204, $response);
    }

    /**
     * Test that the controller properly handles an exception thrown by the daemon conneciton.
     */
    public function testExceptionThrownByDaemonConnectionIsHandledByStoreController()
    {
        $server = factory(Server::class)->make();
        $exception = m::mock(RequestException::class);

        $this->session->shouldReceive('get')->with('server_data.model')->once()->andReturn($server);
        $this->controller->shouldReceive('authorize')->with('save-files', $server)->once()->andReturnNull();
        $this->fileRepository->shouldReceive('setNode')->with($server->node_id)->once()->andThrow($exception);

        $this->writer->shouldReceive('warning')->with($exception)->once()->andReturnNull();
        $exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnNull();

        $response = $this->controller->store($this->request, '1234');
        $this->assertIsJsonResponse($response);
        $this->assertResponseJsonEquals(['error' => trans('exceptions.daemon_connection_failed', ['code' => 'E_CONN_REFUSED'])], $response);
        $this->assertResponseCodeEquals(500, $response);
    }
}
