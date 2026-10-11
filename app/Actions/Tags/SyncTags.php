<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Tags;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Tags\SyncsTags;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;
use Pterodactyl\Services\Tags\TagResolutionService;

final readonly class SyncTags implements SyncsTags
{
    public function __construct(private TagResolutionService $resolver) {}

    /** @param list<string> $values */
    public function syncEgg(Egg $egg, array $values): void
    {
        $this->sync($egg, $egg->tags(), $values);
    }

    /** @param list<string> $values */
    public function syncNodeGames(Node $node, array $values): void
    {
        $this->sync($node, $node->eggTags(), $values, $node->deploymentTags());
    }

    /** @param list<string> $values */
    public function syncNodeDeployments(Node $node, array $values): void
    {
        $this->sync($node, $node->deploymentTags(), $values, $node->eggTags());
    }

    /**
     * A node holds each tag either as a game or as a reservation, never both, so tags
     * already attached as the other kind are rejected rather than left to collide in
     * the pivot table.
     *
     * @template T of Egg|Node
     *
     * @param  T  $parent
     * @param  MorphToMany<Tag, T>  $tags
     * @param  list<string>  $values
     * @param  MorphToMany<Tag, T>|null  $otherKind
     */
    private function sync(Egg|Node $parent, MorphToMany $tags, array $values, ?MorphToMany $otherKind = null): void
    {
        DB::transaction(function () use ($parent, $tags, $values, $otherKind): void {
            $parent->newQuery()->lockForUpdate()->findOrFail($parent->id);
            $ids = $this->resolver->resolve($values);

            $taken = $otherKind?->whereKey($ids)->pluck('tags.name')->implode(', ') ?? '';
            if ($taken !== '') {
                throw ValidationException::withMessages(['tags' => "These tags are already on the node as the other kind: {$taken}."]);
            }

            $tags->sync($ids);
        });
    }
}
