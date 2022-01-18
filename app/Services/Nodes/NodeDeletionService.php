<?php

/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Services\Nodes;

use Pterodactyl\Models\Node;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class NodeDeletionService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Pterodactyl\Contracts\Repository\ServerRepositoryInterface
     */
    protected $serverRepository;

    /**
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * DeletionService constructor.
     */
    public function __construct(
        NodeRepositoryInterface $repository,
        ServerRepositoryInterface $serverRepository,
        Translator $translator
    ) {
        $this->repository = $repository;
        $this->serverRepository = $serverRepository;
        $this->translator = $translator;
    }

    /**
     * Delete a node from the panel if no servers are attached to it.
     *
     * @param int|\Pterodactyl\Models\Node $node
     *
     * @return bool|null
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function handle($node)
    {
        if ($node instanceof Node) {
            $node = $node->id;
        }

        $servers = $this->serverRepository->setColumns('id')->findCountWhere([['node_id', '=', $node]]);
        if ($servers > 0) {
            throw new HasActiveServersException($this->translator->get('exceptions.node.servers_attached'));
        }

        return $this->repository->delete($node);
    }
}
