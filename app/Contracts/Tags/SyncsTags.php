<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Tags;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;

interface SyncsTags
{
    /** @param list<string> $values */
    public function syncEgg(Egg $egg, array $values): void;

    /** @param list<string> $values */
    public function syncNodeGames(Node $node, array $values): void;

    /** @param list<string> $values */
    public function syncNodeDeployments(Node $node, array $values): void;
}
