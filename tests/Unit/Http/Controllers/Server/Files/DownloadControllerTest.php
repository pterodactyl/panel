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
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Server;
use Illuminate\Cache\Repository;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use Pterodactyl\Http\Controllers\Server\Files\DownloadController;

class DownloadControllerTest extends ControllerTestCase
{
    use PHPMock;

    /**
     * @var \Illuminate\Cache\Repository|\Mockery\Mock
     */
    protected $cache;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->cache = m::mock(Repository::class);
    }

    /**
     * Test the download controller redirects correctly.
     */
    public function testIndexController()
    {
        $controller = $this->getController();
        $server = factory(Server::class)->make();
        $server->setRelation('node', factory(Node::class)->make());

        $this->setRequestAttribute('server', $server);

        $controller->shouldReceive('authorize')->with('download-files', $server)->once()->andReturnNull();
        $this->getFunctionMock('\\Pterodactyl\\Http\\Controllers\\Server\\Files', 'str_random')
            ->expects($this->once())->willReturn('randomString');

        $this->cache->shouldReceive('tags')->with(['Server:Downloads'])->once()->andReturnSelf();
        $this->cache->shouldReceive('put')->with('randomString', ['server' => $server->uuid, 'path' => '/my/file.txt'], 5)->once()->andReturnNull();

        $response = $controller->index($this->request, $server->uuidShort, '/my/file.txt');
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectUrlEquals(sprintf(
            '%s://%s:%s/v1/server/file/download/%s', $server->node->scheme, $server->node->fqdn, $server->node->daemonListen, 'randomString'
        ), $response);
    }

    /**
     * Return a mocked instance of the controller to allow access to authorization functionality.
     *
     * @return \Pterodactyl\Http\Controllers\Server\Files\DownloadController|\Mockery\Mock
     */
    private function getController()
    {
        return $this->buildMockedController(DownloadController::class, [$this->cache]);
    }
}
