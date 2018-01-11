<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Tests\Unit\Services\Allocations;

use Exception;
use Mockery as m;
use Tests\TestCase;
use phpmock\phpunit\PHPMock;
use Pterodactyl\Models\Node;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class AssignmentServiceTest extends TestCase
{
    use PHPMock;

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Models\Node
     */
    protected $node;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Services\Allocations\AssignmentService
     */
    protected $service;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        // Due to a bug in PHP, this is necessary since we only have a single test
        // that relies on this mock. If this does not exist the test will fail to register
        // correctly.
        //
        // This can also be avoided if tests were run in isolated processes, or if that test
        // came first, but neither of those are good solutions, so this is the next best option.
        PHPMock::defineFunctionMock('\\Pterodactyl\\Services\\Allocations', 'gethostbyname');

        $this->node = factory(Node::class)->make();
        $this->connection = m::mock(ConnectionInterface::class);
        $this->repository = m::mock(AllocationRepositoryInterface::class);

        $this->service = new AssignmentService($this->repository, $this->connection);
    }

    /**
     * Test a non-CIDR notated IP address without a port range.
     */
    public function testIndividualIpAddressWithoutRange()
    {
        $data = [
            'allocation_ip' => '192.168.1.1',
            'allocation_ports' => ['1024'],
        ];

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('insertIgnore')->with([
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.1',
                'port' => 1024,
                'ip_alias' => null,
                'server_id' => null,
            ],
        ])->once()->andReturn(true);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($this->node->id, $data);
    }

    /**
     * Test a non-CIDR IP address with a port range provided.
     */
    public function testIndividualIpAddressWithRange()
    {
        $data = [
            'allocation_ip' => '192.168.1.1',
            'allocation_ports' => ['1024-1026'],
        ];

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('insertIgnore')->with([
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.1',
                'port' => 1024,
                'ip_alias' => null,
                'server_id' => null,
            ],
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.1',
                'port' => 1025,
                'ip_alias' => null,
                'server_id' => null,
            ],
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.1',
                'port' => 1026,
                'ip_alias' => null,
                'server_id' => null,
            ],
        ])->once()->andReturn(true);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($this->node->id, $data);
    }

    /**
     * Test a non-CIRD IP address with a single port and an alias.
     */
    public function testIndividualIPAddressWithAlias()
    {
        $data = [
            'allocation_ip' => '192.168.1.1',
            'allocation_ports' => ['1024'],
            'allocation_alias' => 'my.alias.net',
        ];

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('insertIgnore')->with([
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.1',
                'port' => 1024,
                'ip_alias' => 'my.alias.net',
                'server_id' => null,
            ],
        ])->once()->andReturn(true);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($this->node->id, $data);
    }

    /**
     * Test that a domain name can be passed in place of an IP address.
     */
    public function testDomainNamePassedInPlaceOfIPAddress()
    {
        $data = [
            'allocation_ip' => 'test-domain.com',
            'allocation_ports' => ['1024'],
        ];

        $this->getFunctionMock('\\Pterodactyl\\Services\\Allocations', 'gethostbyname')
            ->expects($this->once())->willReturn('192.168.1.1');

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('insertIgnore')->with([
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.1',
                'port' => 1024,
                'ip_alias' => null,
                'server_id' => null,
            ],
        ])->once()->andReturn(true);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($this->node->id, $data);
    }

    /**
     * Test that a CIDR IP address without a range works properly.
     */
    public function testCIDRNotatedIPAddressWithoutRange()
    {
        $data = [
            'allocation_ip' => '192.168.1.100/31',
            'allocation_ports' => ['1024'],
        ];

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('insertIgnore')->with([
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.100',
                'port' => 1024,
                'ip_alias' => null,
                'server_id' => null,
            ],
        ])->once()->andReturn(true);

        $this->repository->shouldReceive('insertIgnore')->with([
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.101',
                'port' => 1024,
                'ip_alias' => null,
                'server_id' => null,
            ],
        ])->once()->andReturn(true);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($this->node->id, $data);
    }

    /**
     * Test that a CIDR IP address with a range works properly.
     */
    public function testCIDRNotatedIPAddressOutsideRangeLimit()
    {
        $data = [
            'allocation_ip' => '192.168.1.100/20',
            'allocation_ports' => ['1024'],
        ];

        try {
            $this->service->handle($this->node->id, $data);
        } catch (Exception $exception) {
            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(trans('exceptions.allocations.cidr_out_of_range'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if there are too many ports.
     */
    public function testAllocationWithPortsExceedingLimit()
    {
        $data = [
            'allocation_ip' => '192.168.1.1',
            'allocation_ports' => ['5000-7000'],
        ];

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        try {
            $this->service->handle($this->node->id, $data);
        } catch (Exception $exception) {
            if (! $exception instanceof DisplayException) {
                throw $exception;
            }

            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(trans('exceptions.allocations.too_many_ports'), $exception->getMessage());
        }
    }

    /**
     * Test that an exception is thrown if an invalid port is provided.
     */
    public function testInvalidPortProvided()
    {
        $data = [
            'allocation_ip' => '192.168.1.1',
            'allocation_ports' => ['test123'],
        ];

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();

        try {
            $this->service->handle($this->node->id, $data);
        } catch (Exception $exception) {
            if (! $exception instanceof DisplayException) {
                throw $exception;
            }

            $this->assertInstanceOf(DisplayException::class, $exception);
            $this->assertEquals(trans('exceptions.allocations.invalid_mapping', ['port' => 'test123']), $exception->getMessage());
        }
    }

    /**
     * Test that a model can be passed in place of an ID.
     */
    public function testModelCanBePassedInPlaceOfNodeModel()
    {
        $data = [
            'allocation_ip' => '192.168.1.1',
            'allocation_ports' => ['1024'],
        ];

        $this->connection->shouldReceive('beginTransaction')->withNoArgs()->once()->andReturnNull();
        $this->repository->shouldReceive('insertIgnore')->with([
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.1',
                'port' => 1024,
                'ip_alias' => null,
                'server_id' => null,
            ],
        ])->once()->andReturn(true);
        $this->connection->shouldReceive('commit')->withNoArgs()->once()->andReturnNull();

        $this->service->handle($this->node, $data);
    }
}
