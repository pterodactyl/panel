<?php

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
     * @param \Pterodactyl\Models\Node $node
     *
     * @throws \Pterodactyl\Exceptions\Service\HasActiveServersException
     */
    public function handle(Node $node): void
    {
        $servers = $this->serverRepository->setColumns('id')->findCountWhere([['node_id', '=', $node->id]]);
        if ($servers > 0) {
            throw new HasActiveServersException($this->translator->get('exceptions.node.servers_attached'));
        }

        $this->repository->delete($node->id);
    }
}
