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

namespace Tests\Unit\Services\Servers;

use Exception;
use Mockery as m;
use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\Server;
use Illuminate\Database\DatabaseManager;
use GuzzleHttp\Exception\RequestException;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Pterodactyl\Services\Servers\DetailsModificationService;
use Pterodactyl\Repositories\Daemon\ServerRepository as DaemonServerRepository;

class DetailsModificationServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $database;

    /**
     * @var \Pterodactyl\Repositories\Daemon\ServerRepository
     */
    protected $daemonServerRepository;

    /**
     * @var \GuzzleHttp\Exception\RequestException
     */
    protected $exception;

    /**
     * @var \Pterodactyl\Repositories\Eloquent\ServerRepository
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Servers\DetailsModificationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->database = m::mock(DatabaseManager::class);
        $this->exception = m::mock(RequestException::class)->makePartial();
        $this->daemonServerRepository = m::mock(DaemonServerRepository::class);
        $this->repository = m::mock(ServerRepository::class);

        $this->getFunctionMock('\\Pterodactyl\\Services\\Servers', 'bin2hex')
            ->expects($this->any())->willReturn('randomString');

        $this->service = new DetailsModificationService(
            $this->database,
            $this->daemonServerRepository,
            $this->repository
        );
    }

    /**
     * Test basic updating of core variables when a model is provided.
     */
    public function testEditShouldSkipDatabaseSearchIfModelIsPassed()
    {
        $server = factory(Server::class)->make([
            'owner_id' => 1,
        ]);

        $data = ['owner_id' => 1, 'name' => 'New Name', 'description' => 'New Description'];

        $this->repository->shouldNotReceive('find');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'owner_id' => $data['owner_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'daemonSecret' => $server->daemonSecret,
            ], true, true)->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturn(true);

        $response = $this->service->edit($server, $data);
        $this->assertTrue($response);
    }

    /**
     * Test that repository attempts to find model in database if no model is passed.
     */
    public function testEditShouldGetModelFromRepositoryIfNotPassed()
    {
        $server = factory(Server::class)->make([
            'owner_id' => 1,
        ]);

        $data = ['owner_id' => 1, 'name' => 'New Name', 'description' => 'New Description'];

        $this->repository->shouldReceive('find')->with($server->id)->once()->andReturn($server);
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'owner_id' => $data['owner_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'daemonSecret' => $server->daemonSecret,
            ], true, true)->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturn(true);

        $response = $this->service->edit($server->id, $data);
        $this->assertTrue($response);
    }

    /**
     * Test that the daemon secret is reset if the owner id changes.
     */
    public function testEditShouldResetDaemonSecretIfOwnerIdIsChanged()
    {
        $server = factory(Server::class)->make([
            'owner_id' => 1,
            'node_id' => 1,
        ]);

        $data = ['owner_id' => 2, 'name' => 'New Name', 'description' => 'New Description'];

        $this->repository->shouldNotReceive('find');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'owner_id' => $data['owner_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'daemonSecret' => 'randomString',
            ], true, true)->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->with($server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($server->uuid)->once()->andReturnSelf()
            ->shouldReceive('update')->with([
                'keys' => [
                    $server->daemonSecret => [],
                    'randomString' => DaemonServerRepository::DAEMON_PERMISSIONS,
                ],
            ])->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturn(true);

        $response = $this->service->edit($server, $data);
        $this->assertTrue($response);
    }

    public function testEditShouldResetDaemonSecretIfBooleanValueIsPassed()
    {
        $server = factory(Server::class)->make([
            'owner_id' => 1,
            'node_id' => 1,
        ]);

        $data = ['owner_id' => 1, 'name' => 'New Name', 'description' => 'New Description', 'reset_token' => true];

        $this->repository->shouldNotReceive('find');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'owner_id' => $data['owner_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'daemonSecret' => 'randomString',
            ], true, true)->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->with($server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($server->uuid)->once()->andReturnSelf()
            ->shouldReceive('update')->with([
                'keys' => [
                    $server->daemonSecret => [],
                    'randomString' => DaemonServerRepository::DAEMON_PERMISSIONS,
                ],
            ])->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturn(true);

        $response = $this->service->edit($server, $data);
        $this->assertTrue($response);
    }

    /**
     * Test that a displayable exception is thrown if the daemon responds with an error.
     */
    public function testEditShouldThrowADisplayableExceptionIfDaemonResponseErrors()
    {
        $server = factory(Server::class)->make([
            'owner_id' => 1,
            'node_id' => 1,
        ]);

        $data = ['owner_id' => 2, 'name' => 'New Name', 'description' => 'New Description'];

        $this->repository->shouldNotReceive('find');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'owner_id' => $data['owner_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'daemonSecret' => 'randomString',
            ], true, true)->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->andThrow($this->exception);
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('getStatusCode')->withNoArgs()->once()->andReturn(400);

        $this->database->shouldNotReceive('commit');

        try {
            $this->service->edit($server, $data);
        } catch (Exception $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(
                trans('admin/server.exceptions.daemon_exception', ['code' => 400,]), $exception->getMessage()
            );
        }
    }

    /**
     * Test that an exception not stemming from Guzzle is not thrown as a displayable exception.
     *
     * @expectedException \Exception
     */
    public function testEditShouldNotThrowDisplayableExceptionIfExceptionIsNotThrownByGuzzle()
    {
        $server = factory(Server::class)->make([
            'owner_id' => 1,
            'node_id' => 1,
        ]);

        $data = ['owner_id' => 2, 'name' => 'New Name', 'description' => 'New Description'];

        $this->repository->shouldNotReceive('find');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'owner_id' => $data['owner_id'],
                'name' => $data['name'],
                'description' => $data['description'],
                'daemonSecret' => 'randomString',
            ], true, true)->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->andThrow(new Exception());
        $this->database->shouldNotReceive('commit');

        $this->service->edit($server, $data);
    }

    /**
     * Test that the docker image for a server can be updated if a model is provided.
     */
    public function testDockerImageCanBeUpdatedWhenAServerModelIsProvided()
    {
        $server = factory(Server::class)->make(['node_id' => 1]);

        $this->repository->shouldNotReceive('find');
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'image' => 'new/image',
            ])->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->with($server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($server->uuid)->once()->andReturnSelf()
            ->shouldReceive('update')->with([
                'build' => [
                    'image' => 'new/image',
                ],
            ])->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturn(true);

        $this->service->setDockerImage($server, 'new/image');
    }

    /**
     * Test that the docker image for a server can be updated if a model is provided.
     */
    public function testDockerImageCanBeUpdatedWhenNoModelIsProvided()
    {
        $server = factory(Server::class)->make(['node_id' => 1]);

        $this->repository->shouldReceive('find')->with($server->id)->once()->andReturn($server);
        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'image' => 'new/image',
            ])->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->with($server->node_id)->once()->andReturnSelf()
            ->shouldReceive('setAccessServer')->with($server->uuid)->once()->andReturnSelf()
            ->shouldReceive('update')->with([
                'build' => [
                    'image' => 'new/image',
                ],
            ])->once()->andReturnNull();

        $this->database->shouldReceive('commit')->withNoArgs()->once()->andReturn(true);

        $this->service->setDockerImage($server->id, 'new/image');
    }

    /**
     * Test that an exception thrown by Guzzle is rendered as a displayable exception.
     */
    public function testExceptionThrownByGuzzleWhenSettingDockerImageShouldBeRenderedAsADisplayableException()
    {
        $server = factory(Server::class)->make(['node_id' => 1]);

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'image' => 'new/image',
            ])->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->andThrow($this->exception);
        $this->exception->shouldReceive('getResponse')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('getStatusCode')->withNoArgs()->once()->andReturn(400);

        $this->database->shouldNotReceive('commit');

        try {
            $this->service->setDockerImage($server, 'new/image');
        } catch (Exception $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(
                trans('admin/server.exceptions.daemon_exception', ['code' => 400,]), $exception->getMessage()
            );
        }
    }

    /**
     * Test that an exception not thrown by Guzzle is not transformed to a displayable exception.
     *
     * @expectedException \Exception
     */
    public function testExceptionNotThrownByGuzzleWhenSettingDockerImageShouldNotBeRenderedAsADisplayableException()
    {
        $server = factory(Server::class)->make(['node_id' => 1]);

        $this->database->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('withoutFresh')->withNoArgs()->once()->andReturnSelf()
            ->shouldReceive('update')->with($server->id, [
                'image' => 'new/image',
            ])->once()->andReturnNull();

        $this->daemonServerRepository->shouldReceive('setNode')->andThrow(new Exception());
        $this->database->shouldNotReceive('commit');

        $this->service->setDockerImage($server, 'new/image');
    }
}
