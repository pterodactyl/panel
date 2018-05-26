<?php

namespace Tests\Unit\Services\Allocations;

use Mockery as m;
use Tests\TestCase;
use Pterodactyl\Models\Node;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Services\Allocations\AssignmentService;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class AssignmentServiceTest extends TestCase
{
    /**
     * @var \Illuminate\Database\ConnectionInterface|\Mockery\Mock
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Models\Node
     */
    protected $node;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface|\Mockery\Mock
     */
    protected $repository;

    /**
     * Setup tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->node = factory(Node::class)->make();
        $this->connection = m::mock(ConnectionInterface::class);
        $this->repository = m::mock(AllocationRepositoryInterface::class);
    }

    /**
     * Test a non-CIDR notated IP address without a port range.
     */
    public function testIndividualIpAddressWithoutRange()
    {
        $data = [
            'allocation_ip' => '192.168.1.1',
            'allocation_ports' => ['2222'],
        ];

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();
        $this->repository->shouldReceive('insertIgnore')->with([
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.1',
                'port' => 2222,
                'ip_alias' => null,
                'server_id' => null,
            ],
        ])->once()->andReturn(true);
        $this->connection->shouldReceive('commit')->once()->withNoArgs()->andReturnNull();

        $this->getService()->handle($this->node, $data);
    }

    /**
     * Test a non-CIDR IP address with a port range provided.
     */
    public function testIndividualIpAddressWithRange()
    {
        $data = [
            'allocation_ip' => '192.168.1.1',
            'allocation_ports' => ['1025-1027'],
        ];

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();
        $this->repository->shouldReceive('insertIgnore')->once()->with([
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
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.1',
                'port' => 1027,
                'ip_alias' => null,
                'server_id' => null,
            ],
        ])->andReturn(true);
        $this->connection->shouldReceive('commit')->once()->withNoArgs()->andReturnNull();

        $this->getService()->handle($this->node, $data);
    }

    /**
     * Test a non-CIDR IP address with a single port and an alias.
     */
    public function testIndividualIPAddressWithAlias()
    {
        $data = [
            'allocation_ip' => '192.168.1.1',
            'allocation_ports' => ['2222'],
            'allocation_alias' => 'my.alias.net',
        ];

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();
        $this->repository->shouldReceive('insertIgnore')->once()->with([
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.1',
                'port' => 2222,
                'ip_alias' => 'my.alias.net',
                'server_id' => null,
            ],
        ])->andReturn(true);
        $this->connection->shouldReceive('commit')->once()->withNoArgs()->andReturnNull();

        $this->getService()->handle($this->node, $data);
    }

    /**
     * Test that a domain name can be passed in place of an IP address.
     */
    public function testDomainNamePassedInPlaceOfIPAddress()
    {
        $data = [
            'allocation_ip' => 'unit-test-static.pterodactyl.io',
            'allocation_ports' => ['2222'],
        ];

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();
        $this->repository->shouldReceive('insertIgnore')->once()->with([
            [
                'node_id' => $this->node->id,
                'ip' => '127.0.0.1',
                'port' => 2222,
                'ip_alias' => null,
                'server_id' => null,
            ],
        ])->andReturn(true);
        $this->connection->shouldReceive('commit')->once()->withNoArgs()->andReturnNull();

        $this->getService()->handle($this->node, $data);
    }

    /**
     * Test that a CIDR IP address without a range works properly.
     */
    public function testCIDRNotatedIPAddressWithoutRange()
    {
        $data = [
            'allocation_ip' => '192.168.1.100/31',
            'allocation_ports' => ['2222'],
        ];

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();
        $this->repository->shouldReceive('insertIgnore')->once()->with([
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.100',
                'port' => 2222,
                'ip_alias' => null,
                'server_id' => null,
            ],
        ])->andReturn(true);

        $this->repository->shouldReceive('insertIgnore')->once()->with([
            [
                'node_id' => $this->node->id,
                'ip' => '192.168.1.101',
                'port' => 2222,
                'ip_alias' => null,
                'server_id' => null,
            ],
        ])->andReturn(true);
        $this->connection->shouldReceive('commit')->once()->withNoArgs()->andReturnNull();

        $this->getService()->handle($this->node, $data);
    }

    /**
     * Test that a CIDR IP address with a range works properly.
     *
     * @expectedException \Pterodactyl\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @expectedExceptionMessage CIDR notation only allows masks between /25 and /32.
     */
    public function testCIDRNotatedIPAddressOutsideRangeLimit()
    {
        $data = [
            'allocation_ip' => '192.168.1.100/20',
            'allocation_ports' => ['2222'],
        ];

        $this->getService()->handle($this->node, $data);
    }

    /**
     * Test that an exception is thrown if there are too many ports.
     *
     * @expectedException \Pterodactyl\Exceptions\Service\Allocation\TooManyPortsInRangeException
     * @expectedExceptionMessage Adding more than 1000 ports in a single range at once is not supported.
     */
    public function testAllocationWithPortsExceedingLimit()
    {
        $data = [
            'allocation_ip' => '192.168.1.1',
            'allocation_ports' => ['5000-7000'],
        ];

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();

        $this->getService()->handle($this->node, $data);
    }

    /**
     * Test that an exception is thrown if an invalid port is provided.
     *
     * @expectedException \Pterodactyl\Exceptions\Service\Allocation\InvalidPortMappingException
     * @expectedExceptionMessage The mapping provided for test123 was invalid and could not be processed.
     */
    public function testInvalidPortProvided()
    {
        $data = [
            'allocation_ip' => '192.168.1.1',
            'allocation_ports' => ['test123'],
        ];

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();
        $this->getService()->handle($this->node, $data);
    }

    /**
     * Test that ports outside of defined limits throw an error.
     *
     * @param array $ports
     *
     * @dataProvider invalidPortsDataProvider
     * @expectedException \Pterodactyl\Exceptions\Service\Allocation\PortOutOfRangeException
     * @expectedExceptionMessage Ports in an allocation must be greater than 1024 and less than or equal to 65535.
     */
    public function testPortRangeOutsideOfRangeLimits(array $ports)
    {
        $data = ['allocation_ip' => '192.168.1.1', 'allocation_ports' => $ports];

        $this->connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnNull();
        $this->getService()->handle($this->node, $data);
    }

    /**
     * Provide ports and ranges of ports that exceed the viable port limits for the software.
     *
     * @return array
     */
    public function invalidPortsDataProvider(): array
    {
        return [
            [['65536']],
            [['1024']],
            [['1000']],
            [['0']],
            [['65530-65540']],
            [['65540-65560']],
            [[PHP_INT_MAX]],
        ];
    }

    /**
     * Returns an instance of the service with mocked dependencies for testing.
     *
     * @return \Pterodactyl\Services\Allocations\AssignmentService
     */
    private function getService(): AssignmentService
    {
        return new AssignmentService($this->repository, $this->connection);
    }
}
