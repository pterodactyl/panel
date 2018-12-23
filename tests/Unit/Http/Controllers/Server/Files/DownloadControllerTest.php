<?php

namespace Tests\Unit\Http\Controllers\Server\Files;

use Mockery as m;
use Pterodactyl\Models\Node;
use Tests\Traits\MocksUuids;
use Pterodactyl\Models\Server;
use Illuminate\Cache\Repository;
use Tests\Unit\Http\Controllers\ControllerTestCase;
use Pterodactyl\Http\Controllers\Server\Files\DownloadController;

class DownloadControllerTest extends ControllerTestCase
{
    use MocksUuids;

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

        $this->cache->shouldReceive('put')
            ->once()
            ->with('Server:Downloads:' . $this->getKnownUuid(), ['server' => $server->uuid, 'path' => '/my/file.txt'], 5)
            ->andReturnNull();

        $response = $controller->index($this->request, $server->uuidShort, '/my/file.txt');
        $this->assertIsRedirectResponse($response);
        $this->assertRedirectUrlEquals(sprintf(
            '%s://%s:%s/v1/server/file/download/%s',
            $server->node->scheme,
            $server->node->fqdn,
            $server->node->daemonListen,
            $this->getKnownUuid()
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
