<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Tags;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
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
        $this->sync($node, $node->eggTags(), $values);
    }

    /** @param list<string> $values */
    public function syncNodeDeployments(Node $node, array $values): void
    {
        $this->sync($node, $node->deploymentTags(), $values);
    }

    /**
     * @template T of Egg|Node
     *
     * @param  T  $parent
     * @param  MorphToMany<Tag, T>  $tags
     * @param  list<string>  $values
     */
    private function sync(Egg|Node $parent, MorphToMany $tags, array $values): void
    {
        DB::transaction(function () use ($parent, $tags, $values): void {
            $parent->newQuery()->lockForUpdate()->findOrFail($parent->id);
            $tags->sync($this->resolver->resolve($values));
        });
    }
}
