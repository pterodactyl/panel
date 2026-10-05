<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Deployment;

use Illuminate\Database\Eloquent\Builder;
use Pterodactyl\Models\Node;

/**
 * Single source of truth for tag-based node eligibility: a node's egg tags are
 * OR-matched against the deploying egg's tags (no egg tags = accepts any egg),
 * while its deployment tags are exact-matched against the declared deploy tags in
 * both directions, so a reservation is never bypassed by the multi-game OR.
 */
class NodeTagGate
{
    /**
     * Constrain a Node query to the tag-eligible nodes for the given egg/deploy
     * tags.
     *
     * @param  Builder<Node>  $query
     * @param  string[]  $eggTags  E
     * @param  string[]  $deployTags  D
     */
    public function apply(Builder $query, array $eggTags, array $deployTags): void
    {
        // Game (OR): no egg tags at all, or at least one shared with the egg.
        $query->where(function (Builder $outer) use ($eggTags): void {
            $outer->whereDoesntHave('eggTags');

            if ($eggTags !== []) {
                $outer->orWhereHas('eggTags', fn (Builder $relation) => $relation->whereIn('tags.slug', $eggTags));
            }
        });

        $this->applyDeploymentTags($query, $deployTags);
    }

    /**
     * Constrain a Node query to the deployment-tag (reservation) half of the gate,
     * leaving the egg/game half out. This is what an eligible-nodes preview uses: it
     * can reproduce the reservation match exactly, but not the egg match, because
     * the egg is not chosen until creation.
     *
     * @param  Builder<Node>  $query
     * @param  string[]  $deployTags  D
     */
    public function applyDeploymentTags(Builder $query, array $deployTags): void
    {
        // Reservation: no deployment tag the deploy didn't declare.
        $query->whereDoesntHave('deploymentTags', fn (Builder $relation) => $relation->whereNotIn('tags.slug', $deployTags));

        // Targeting: every declared deploy tag is on the node.
        foreach ($deployTags as $slug) {
            $query->whereHas('deploymentTags', fn (Builder $relation) => $relation->where('tags.slug', $slug));
        }
    }

    /**
     * Whether a single node, given its kind-split tag slugs, is eligible for the
     * given egg/deploy tags.
     *
     * @param  string[]  $nodeEggTags
     * @param  string[]  $nodeDeploymentTags
     * @param  string[]  $eggTags  E
     * @param  string[]  $deployTags  D
     */
    public function allows(array $nodeEggTags, array $nodeDeploymentTags, array $eggTags, array $deployTags = []): bool
    {
        // Game (OR): egg tags only constrain when the node declares some.
        if ($nodeEggTags !== [] && array_intersect($nodeEggTags, $eggTags) === []) {
            return false;
        }

        // Reservation: every node deployment tag must be declared by the deploy.
        if (array_diff($nodeDeploymentTags, $deployTags) !== []) {
            return false;
        }

        // Targeting: every declared deploy tag must be on the node.
        return array_diff($deployTags, $nodeDeploymentTags) === [];
    }
}
