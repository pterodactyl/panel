<?php

namespace Pterodactyl\Services\Nodes;

use Pterodactyl\Models\Node;
use Pterodactyl\Services\Nodes\NodeCreationService;

class NodeCloneService
{
    public function __construct(private NodeCreationService $creationService)
    {
    }

    public function handle(Node $sourceNode, array $overrides): Node
    {
        $data = $sourceNode->only($sourceNode->getFillable());

        $data = array_merge($data, $overrides);

        return $this->creationService->handle($data);
    }
}
