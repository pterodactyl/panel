<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Allocations;

use IPTools\Network;
use Pterodactyl\Models\Node;
use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Contracts\Repository\AllocationRepositoryInterface;

class AssignmentService
{
    const CIDR_MAX_BITS = 27;
    const CIDR_MIN_BITS = 32;
    const PORT_RANGE_LIMIT = 1000;
    const PORT_RANGE_REGEX = '/^(\d{1,5})-(\d{1,5})$/';

    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * @var \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface
     */
    protected $repository;

    /**
     * AssignmentService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\AllocationRepositoryInterface $repository
     * @param \Illuminate\Database\ConnectionInterface                        $connection
     */
    public function __construct(
        AllocationRepositoryInterface $repository,
        ConnectionInterface $connection
    ) {
        $this->connection = $connection;
        $this->repository = $repository;
    }

    /**
     * Insert allocations into the database and link them to a specific node.
     *
     * @param int|\Pterodactyl\Models\Node $node
     * @param array                        $data
     *
     * @throws \Pterodactyl\Exceptions\DisplayException
     */
    public function handle($node, array $data)
    {
        if ($node instanceof Node) {
            $node = $node->id;
        }

        $explode = explode('/', $data['allocation_ip']);
        if (count($explode) !== 1) {
            if (! ctype_digit($explode[1]) || ($explode[1] > self::CIDR_MIN_BITS || $explode[1] < self::CIDR_MAX_BITS)) {
                throw new DisplayException(trans('exceptions.allocations.cidr_out_of_range'));
            }
        }

        $this->connection->beginTransaction();
        foreach (Network::parse(gethostbyname($data['allocation_ip'])) as $ip) {
            foreach ($data['allocation_ports'] as $port) {
                if (! is_digit($port) && ! preg_match(self::PORT_RANGE_REGEX, $port)) {
                    throw new DisplayException(trans('exceptions.allocations.invalid_mapping', ['port' => $port]));
                }

                $insertData = [];
                if (preg_match(self::PORT_RANGE_REGEX, $port, $matches)) {
                    $block = range($matches[1], $matches[2]);

                    if (count($block) > self::PORT_RANGE_LIMIT) {
                        throw new DisplayException(trans('exceptions.allocations.too_many_ports'));
                    }

                    foreach ($block as $unit) {
                        $insertData[] = [
                            'node_id' => $node,
                            'ip' => $ip->__toString(),
                            'port' => (int) $unit,
                            'ip_alias' => array_get($data, 'allocation_alias'),
                            'server_id' => null,
                        ];
                    }
                } else {
                    $insertData[] = [
                        'node_id' => $node,
                        'ip' => $ip->__toString(),
                        'port' => (int) $port,
                        'ip_alias' => array_get($data, 'allocation_alias'),
                        'server_id' => null,
                    ];
                }

                $this->repository->insertIgnore($insertData);
            }
        }

        $this->connection->commit();
    }
}
