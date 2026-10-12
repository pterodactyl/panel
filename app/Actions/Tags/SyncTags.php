<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Tags;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Pterodactyl\Contracts\Tags\SyncsTags;
use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Tag;
use Pterodactyl\Rules\TagSlug;

final readonly class SyncTags implements SyncsTags
{
    /**
     * Rules for a tag value given as text: an existing tag's id as a numeric string, or the
     * name of a tag to find or create.
     *
     * @var list<string>
     */
    public const array TEXT = ['string', 'max:191'];

    /** @return NormalizedValidationRules */
    public static function rules(): array
    {
        return [
            'tags' => ['present', 'array'],
            // A numeric value is read as an existing tag id rather than a new name.
            // \Pterodactyl\Rules\TagSlug is what keeps those two spaces from
            // overlapping, so no rule is needed here beyond the length bound.
            'tags.*' => [Rule::anyOf([['integer:strict'], self::TEXT])],
        ];
    }

    /**
     * An integer id becomes the numeric string the panel sends.
     *
     * @param  list<mixed>  $values
     * @return list<string>
     */
    public static function values(array $values): array
    {
        $input = Validator::make(['tags' => $values], self::rules())->safe();

        return array_map(fn (int|string $key): string => $input->string('tags.'.$key)->toString(), array_keys($input->array('tags')));
    }

    public function syncEgg(Egg $egg, array $values): void
    {
        $this->sync($egg, $egg->tags(), self::values($values));
    }

    public function syncNodeGames(Node $node, array $values): void
    {
        $this->sync($node, $node->eggTags(), self::values($values), $node->deploymentTags());
    }

    public function syncNodeDeployments(Node $node, array $values): void
    {
        $this->sync($node, $node->deploymentTags(), self::values($values), $node->eggTags());
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
            $ids = $this->resolveIds($values);

            $taken = $otherKind?->whereKey($ids)->pluck('tags.name')->implode(', ') ?? '';
            if ($taken !== '') {
                throw ValidationException::withMessages(['tags' => "These tags are already on the node as the other kind: {$taken}."]);
            }

            $tags->sync($ids);
        });
    }

    /**
     * The ids of the given tags, creating a tag for each new name. This is what lets the node
     * and egg tag pickers create a tag by typing its name.
     *
     * The values are validated first (rules() bounds each name to at most 191 characters);
     * with the numeric check below that satisfies every rule in CreateTag::rules(), so tags
     * created here need no separate check.
     *
     * @param  string[]  $values  existing tag ids or names
     * @return int[]
     */
    private function resolveIds(array $values): array
    {
        $values = array_values(array_unique(array_filter(array_map(mb_trim(...), $values), fn (string $value): bool => $value !== '')));
        if ($values === []) {
            return [];
        }

        $tags = Tag::matchingKeysOrSlugs($values);
        $byId = $tags->keyBy('id');
        $bySlug = $tags->keyBy('slug');
        $ids = [];

        foreach ($values as $value) {
            // A purely numeric value is either an existing tag id or a leftover legacy nest
            // id, never a slug to create (see TagSlug). It is resolved if it names a real tag
            // and dropped otherwise, rather than creating a tag named after a stale nest id.
            if (TagSlug::isReserved($value)) {
                $existing = $byId->get($value)?->id;

                if ($existing !== null) {
                    $ids[] = $existing;
                }

                continue;
            }

            // Anything else is a tag name. The slug is the typed text verbatim, so a name keeps
            // its exact casing and spacing and matches a backfilled nest tag of the same name.
            // An exact built-in slug still resolves to its canonical, enum-named tag.
            $name = EggSpecificTags::tryFrom($value)?->friendlyName() ?? $value;

            $ids[] = ($bySlug->get($value) ?? Tag::query()->firstOrCreate(['slug' => $value], ['name' => $name]))->id;
        }

        return array_values(array_unique($ids));
    }
}
