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
use Pterodactyl\Models\Server;
use Tests\Traits\MocksRequestException;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\PterodactylException;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use Pterodactyl\Http\Requests\Server\UpdateFileContentsFormRequest;
use Pterodactyl\Contracts\Repository\Daemon\FileRepositoryInterface;
use Pterodactyl\Http\Controllers\Server\Files\FileActionsController;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class FileActionsControllerTest extends ControllerTestCase
{
    use MocksRequestException;

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

        $this->repository = m::mock(FileRepositoryInterface::class);
    }

    /**
     * Test the index view controller.
     */
    public function testIndexController()
    {
        $controller = $this->getController();
        $server = factory(Server::class)->make();

        $this->setRequestAttribute('server', $server);
        $this->mockInjectJavascript();

        $controller->shouldReceive('authorize')->with('list-files', $server)->once()->andReturnNull();
        $this->request->shouldReceive('user->can')->andReturn(true);

        $response = $controller->index($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('server.files.index', $response);
    }

    /**
     * Test the file creation view controller.
     *
     * @dataProvider directoryNameProvider
     */
    public function testCreateController($directory, $expected)
    {
        $controller = $this->getController();
        $server = factory(Server::class)->make();

        $this->setRequestAttribute('server', $server);
        $this->mockInjectJavascript();

        $controller->shouldReceive('authorize')->with('create-files', $server)->once()->andReturnNull();
        $this->request->shouldReceive('get')->with('dir')->andReturn($directory);

        $response = $controller->create($this->request);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('server.files.add', $response);
        $this->assertViewHasKey('directory', $response);
        $this->assertViewKeyEquals('directory', $expected, $response);
    }

    /**
     * Test the update controller.
     *
     * @dataProvider fileNameProvider
     */
    public function testUpdateController($file, $expected)
    {
        $this->setRequestMockClass(UpdateFileContentsFormRequest::class);

        $controller = $this->getController();
        $server = factory(Server::class)->make();

        $this->setRequestAttribute('server', $server);
        $this->setRequestAttribute('server_token', 'abc123');
        $this->setRequestAttribute('file_stats', 'fileStatsObject');
        $this->mockInjectJavascript(['stat' => 'fileStatsObject']);

        $this->repository->shouldReceive('setServer')->with($server)->once()->andReturnSelf()
            ->shouldReceive('setToken')->with('abc123')->once()->andReturnSelf()
            ->shouldReceive('getContent')->with($file)->once()->andReturn('test');

        $response = $controller->view($this->request, '1234', $file);
        $this->assertIsViewResponse($response);
        $this->assertViewNameEquals('server.files.edit', $response);
        $this->assertViewHasKey('file', $response);
        $this->assertViewHasKey('stat', $response);
        $this->assertViewHasKey('contents', $response);
        $this->assertViewHasKey('directory', $response);
        $this->assertViewKeyEquals('file', $file, $response);
        $this->assertViewKeyEquals('stat', 'fileStatsObject', $response);
        $this->assertViewKeyEquals('contents', 'test', $response);
        $this->assertViewKeyEquals('directory', $expected, $response);
    }

    /**
     * Test that an exception is handled correctly in the controller.
     */
    public function testExceptionRenderedByUpdateController()
    {
        $this->setRequestMockClass(UpdateFileContentsFormRequest::class);
        $this->configureExceptionMock();

        $controller = $this->getController();
        $server = factory(Server::class)->make();

        $this->setRequestAttribute('server', $server);
        $this->setRequestAttribute('server_token', 'abc123');
        $this->setRequestAttribute('file_stats', 'fileStatsObject');

        $this->repository->shouldReceive('setServer')->with($server)->once()->andThrow($this->getExceptionMock());

        try {
            $controller->view($this->request, '1234', 'file.txt');
        } catch (PterodactylException $exception) {
            $this->assertInstanceOf(DaemonConnectionException::class, $exception);
            $this->assertInstanceOf(RequestException::class, $exception->getPrevious());
        }
    }

    /**
     * Provides a list of directory names and the expected output from formatting.
     *
     * @return array
     */
    public function directoryNameProvider()
    {
        return [
            [null, ''],
            ['/', ''],
            ['', ''],
            ['my/directory', 'my/directory/'],
            ['/my/directory/', 'my/directory/'],
            ['/////my/directory////', 'my/directory/'],
        ];
    }

    /**
     * Provides a list of file names and the expected output from formatting.
     *
     * @return array
     */
    public function fileNameProvider()
    {
        return [
            ['/my/file.txt', 'my/'],
            ['my/file.txt', 'my/'],
            ['file.txt', '/'],
            ['/file.txt', '/'],
            ['./file.txt', '/'],
        ];
    }

    /**
     * Return a mocked instance of the controller to allow access to authorization functionality.
     *
     * @return \Pterodactyl\Http\Controllers\Server\Files\FileActionsController|\Mockery\Mock
     */
    private function getController()
    {
        return $this->buildMockedController(FileActionsController::class, [$this->repository]);
    }
}
