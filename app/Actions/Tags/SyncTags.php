<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Tags;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Tags\SyncsTags;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;
use Pterodactyl\Rules\TagSlug;

final readonly class SyncTags implements SyncsTags
{
    /** @var list<string> */
    public const array TEXT = ['string', 'max:191'];

    /** @return NormalizedValidationRules */
    public static function rules(): array
    {
        return [
            'tags' => ['present', 'array'],
            'tags.*' => [Rule::anyOf([['integer:strict'], self::TEXT])],
        ];
    }

    public function syncEgg(Egg $egg, array $values): void
    {
        $this->sync($egg, $egg->tags(), $values);
    }

    public function syncNodeGames(Node $node, array $values): void
    {
        $this->sync($node, $node->eggTags(), $values, otherKind: $node->deploymentTags());
    }

    public function syncNodeDeployments(Node $node, array $values): void
    {
        $this->sync($node, $node->deploymentTags(), $values, otherKind: $node->eggTags());
    }

    /**
     * @template T of Egg|Node
     *
     * @param  T  $parent
     * @param  MorphToMany<Tag, T>  $tags
     * @param  list<mixed>  $values
     * @param  MorphToMany<Tag, T>|null  $otherKind
     */
    private function sync(Egg|Node $parent, MorphToMany $tags, array $values, ?MorphToMany $otherKind = null): void
    {
        /** @var array<int|string> $values */
        $values = Validator::validate(['tags' => $values], self::rules())['tags'];

        DB::transaction(function () use ($parent, $tags, $values, $otherKind): void {
            $parent->newQuery()->lockForUpdate()->findOrFail($parent->getKey());
            $resolved = $this->resolve(collect($values));

            // A node holds each tag either as a game or as a reservation, never both.
            $clashing = $otherKind?->whereKey($resolved->modelKeys())->pluck('tags.name') ?? collect();
            if ($clashing->isNotEmpty()) {
                throw ValidationException::withMessages(['tags' => "These tags are already on the node as the other kind: {$clashing->implode(', ')}."]);
            }

            $tags->sync($resolved);
        });
    }

    /**
     * A value that matches a tag's id or slug resolves to that tag. Any other text is the name
     * of a new tag, used verbatim as its slug; a number matching no tag is a stale nest id and
     * is dropped (TagSlug keeps numbers out of the slug space).
     *
     * @param  Collection<array-key, int|string>  $values
     * @return EloquentCollection<int, Tag>
     */
    private function resolve(Collection $values): EloquentCollection
    {
        $values = $values->map(fn (int|string $value): string => mb_trim((string) $value))->filter(filled(...))->unique()->values();
        if ($values->isEmpty()) {
            return new EloquentCollection;
        }

        $existing = Tag::matchingKeysOrSlugs($values->all());
        $newNames = $values->reject(TagSlug::isReserved(...))->diff($existing->map(fn (Tag $tag): string => $tag->slug));

        return $existing->merge(Tag::findOrCreateFromSlugs($newNames->all()));
    }
}
