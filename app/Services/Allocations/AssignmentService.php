<?php

namespace App\Services\Allocations;

use App\Models\Node;
use IPTools\Network;
use Illuminate\Support\Arr;
use Illuminate\Database\ConnectionInterface;
use App\Contracts\Repository\AllocationRepositoryInterface;
use App\Exceptions\Service\Allocation\CidrOutOfRangeException;
use App\Exceptions\Service\Allocation\PortOutOfRangeException;
use App\Exceptions\Service\Allocation\InvalidPortMappingException;
use App\Exceptions\Service\Allocation\TooManyPortsInRangeException;

class AssignmentService
{
    const CIDR_MAX_BITS = 27;
    const CIDR_MIN_BITS = 32;
    const PORT_FLOOR = 1024;
    const PORT_CEIL = 65535;
    const PORT_RANGE_LIMIT = 1000;
    const PORT_RANGE_REGEX = '/^(\d{4,5})-(\d{4,5})$/';

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \App\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $repository;

    /**
     * AssignmentService constructor.
     *
     * @param \App\Contracts\Repository\AllocationRepositoryInterface $repository
     * @param \Illuminate\Database\ConnectionInterface                        $connection
     */
    public function __construct(AllocationRepositoryInterface $repository, ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->repository = $repository;
    }

    /**
     * Insert allocations into the database and link them to a specific node.
     *
     * @param \App\Models\Node $node
     * @param array                    $data
     *
     * @throws \App\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \App\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \App\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \App\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    public function handle(Node $node, array $data)
    {
        $explode = explode('/', $data['allocation_ip']);
        if (count($explode) !== 1) {
            if (! ctype_digit($explode[1]) || ($explode[1] > self::CIDR_MIN_BITS || $explode[1] < self::CIDR_MAX_BITS)) {
                throw new CidrOutOfRangeException;
            }
        }

        $this->connection->beginTransaction();
        foreach (Network::parse(gethostbyname($data['allocation_ip'])) as $ip) {
            foreach ($data['allocation_ports'] as $port) {
                if (! is_digit($port) && ! preg_match(self::PORT_RANGE_REGEX, $port)) {
                    throw new InvalidPortMappingException($port);
                }

                $insertData = [];
                if (preg_match(self::PORT_RANGE_REGEX, $port, $matches)) {
                    $block = range($matches[1], $matches[2]);

                    if (count($block) > self::PORT_RANGE_LIMIT) {
                        throw new TooManyPortsInRangeException;
                    }

                    if ((int) $matches[1] <= self::PORT_FLOOR || (int) $matches[2] > self::PORT_CEIL) {
                        throw new PortOutOfRangeException;
                    }

                    foreach ($block as $unit) {
                        $insertData[] = [
                            'node_id' => $node->id,
                            'ip' => $ip->__toString(),
                            'port' => (int) $unit,
                            'ip_alias' => Arr::get($data, 'allocation_alias'),
                            'server_id' => null,
                        ];
                    }
                } else {
                    if ((int) $port <= self::PORT_FLOOR || (int) $port > self::PORT_CEIL) {
                        throw new PortOutOfRangeException;
                    }

                    $insertData[] = [
                        'node_id' => $node->id,
                        'ip' => $ip->__toString(),
                        'port' => (int) $port,
                        'ip_alias' => Arr::get($data, 'allocation_alias'),
                        'server_id' => null,
                    ];
                }

                $this->repository->insertIgnore($insertData);
            }
        }

        $this->connection->commit();
    }
}
