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
use Illuminate\Support\Carbon;
use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Rules\TagSlug;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $color
 * @property int|null $legacy_nest_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Collection|Node[] $nodes
 * @property int|null $nodes_count
 * @property Collection|Egg[] $eggs
 * @property int|null $eggs_count
 */
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

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'tag';

    /**
     * Fields that are searchable.
     *
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
     * The eggs carrying this tag.
     *
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
        // SAFETY: the declared union is either one string or a list of strings; array casting only wraps the scalar case.
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
