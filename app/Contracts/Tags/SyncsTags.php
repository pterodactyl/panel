<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Tags;

use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;

/**
 * Replaces the full set of tags of one kind on an egg or a node. Each value is an existing
 * tag's id, as an integer or a numeric string, or the name of a tag to find or create. The
 * values are validated here; errors are reported under `tags` and `tags.N`.
 */
interface SyncsTags
{
    /** @param list<mixed> $values */
    public function syncEgg(Egg $egg, array $values): void;

    /**
     * Replace the games a node accepts. A tag the node holds as a reservation is refused.
     *
     * @param  list<mixed>  $values
     */
    public function syncNodeGames(Node $node, array $values): void;

    /**
     * Replace the reservations on a node. A tag the node accepts as a game is refused.
     *
     * @param  list<mixed>  $values
     */
    public function syncNodeDeployments(Node $node, array $values): void;
}
