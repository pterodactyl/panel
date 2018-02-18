<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Nodes;

use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;

class NodeCreationService
{
    const DAEMON_SECRET_LENGTH = 36;

    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * CreationService constructor.
     *
     * @param \Pterodactyl\Contracts\Repository\NodeRepositoryInterface $repository
     */
    public function __construct(NodeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a new node on the panel.
     *
     * @param array $data
     * @return \Pterodactyl\Models\Node
     *
     * @throws \Pterodactyl\Exceptions\Model\DataValidationException
     */
    public function handle(array $data)
    {
        $data['daemonSecret'] = str_random(self::DAEMON_SECRET_LENGTH);

        return $this->repository->create($data);
    }
}
