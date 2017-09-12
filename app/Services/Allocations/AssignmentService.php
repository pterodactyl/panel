<?php
/*
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
                if (! ctype_digit($port) && ! preg_match(self::PORT_RANGE_REGEX, $port)) {
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
