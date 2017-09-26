<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Nodes;

use Mockery as m;
use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

class NodeCreationServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Nodes\NodeCreationService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository = m::mock(NodeRepositoryInterface::class);

        $this->service = new NodeCreationService($this->repository);
    }

    /**
     * Test that a node is created and a daemon secret token is created.
     */
    public function testNodeIsCreatedAndDaemonSecretIsGenerated()
    {
        $this->getFunctionMock('\\Pterodactyl\\Services\\Nodes', 'str_random')
            ->expects($this->once())->willReturn('random_string');

        $this->repository->shouldReceive('create')->with([
            'name' => 'NodeName',
            'daemonSecret' => 'random_string',
        ])->once()->andReturnNull();

        $this->assertNull($this->service->handle(['name' => 'NodeName']));
    }
}
