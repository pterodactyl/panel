<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Rules\TagSlug;

#[Fillable([
    'name',
    'slug',
    'color',
    'legacy_nest_id',
])]
#[RouteKey('id')]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    public const string RESOURCE_NAME = 'tag';

    /**
     * @var list<string>
     */
    public static array $searchableColumns = [
        'name',
        'slug',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'legacy_nest_id' => 'integer',
        ];
    }

    /**
     * @param  list<string>  $identifiers
     * @return Collection<int, self>
     */
    public static function matchingKeysOrSlugs(array $identifiers): Collection
    {
        $query = self::query();
        self::filterKeysOrSlugs($query, $identifiers);

        return $query->get(['id', 'slug']);
    }

    /**
     * The ids of the given tags, creating a tag for each new name. This is what lets the node
     * and egg tag pickers create a tag by typing its name.
     *
     * SyncTags validates the values first (it bounds each name to at most 191 characters);
     * with the numeric check below that satisfies every rule in CreateTag::rules(), so tags
     * created here need no separate check.
     *
     * @param  string[]  $values  existing tag ids or names
     * @return int[]
     */
    public static function resolveIds(array $values): array
    {
        $values = array_values(array_unique(array_filter(array_map(mb_trim(...), $values), fn (string $value): bool => $value !== '')));
        if ($values === []) {
            return [];
        }

        $tags = self::matchingKeysOrSlugs($values);
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

            $ids[] = ($bySlug->get($value) ?? self::query()->firstOrCreate(['slug' => $value], ['name' => $name]))->id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * The nodes carrying this tag, under either pivot kind. The deploy gate reads
     * the kind-scoped Node::eggTags()/deploymentTags() instead.
     *
     * @return MorphToMany<Node, $this>
     */
    public function nodes(): MorphToMany
    {
        return $this->morphedByMany(Node::class, 'taggable', 'taggables', 'tag_id', 'taggable_id');
    }

    /**
     * @return MorphToMany<Egg, $this>
     */
    public function eggs(): MorphToMany
    {
        return $this->morphedByMany(Egg::class, 'taggable', 'taggables', 'tag_id', 'taggable_id');
    }

    /**
     * Whether this is a built-in "special" game tag whose presentation is owned by
     * the EggSpecificTags enum, and which therefore cannot be edited.
     */
    public function isPredefined(): bool
    {
        return EggSpecificTags::isSpecial($this->slug);
    }

    /**
     * The chip colour to render: the enum's brand colour for built-in tags,
     * otherwise the operator-chosen colour.
     */
    public function effectiveColor(): ?string
    {
        return $this->isPredefined() ? EggSpecificTags::from($this->slug)->color() : $this->color;
    }

    /**
     * The display name to render: the enum's friendly name for built-in tags,
     * otherwise the operator-chosen name.
     */
    public function effectiveName(): string
    {
        return $this->isPredefined() ? EggSpecificTags::from($this->slug)->friendlyName() : $this->name;
    }

    /**
     * Match a tag by either its primary key or its slug - pass a single identifier
     * or a list of them. Callers can therefore mix ids and slugs freely: a slug can
     * never be purely numeric ({@see TagSlug} forbids it), so the
     * two identifier spaces are disjoint and the match is unambiguous.
     *
     * @param  Builder<self>  $query
     * @param  string|array<array-key, string>  $identifier
     */
    #[Scope]
    protected function whereKeyOrSlug(Builder $query, string|array $identifier): void
    {
        $identifiers = array_values((array) $identifier);

        self::filterKeysOrSlugs($query, $identifiers);
    }

    /**
     * @param  Builder<self>  $query
     * @param  list<string>  $identifiers
     */
    private static function filterKeysOrSlugs(Builder $query, array $identifiers): void
    {
        $keys = array_values(array_filter($identifiers, fn (string $value): bool => preg_match('/^\d+$/', $value) === 1));
        $slugColumn = $query->getModel()->qualifyColumn('slug');

        $query->where(function (Builder $inner) use ($identifiers, $keys, $slugColumn): void {
            if ($keys !== []) {
                $inner->whereKey($keys);
            }

            $inner->orWhereIn($slugColumn, $identifiers);
        });
    }
}
