<?php

namespace Pterodactyl\Services\Nodes;

use Pterodactyl\Models\Node;
use Illuminate\Contracts\Translation\Translator;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Exceptions\Service\HasActiveServersException;
use Pterodactyl\Contracts\Repository\ServerRepositoryInterface;

class NodeDeletionService
{
    protected NodeRepositoryInterface $repository;

    protected ServerRepositoryInterface $serverRepository;

    protected Translator $translator;

    /**
     * NodeDeletionService constructor.
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
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function handle(int|Node $node): int
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
