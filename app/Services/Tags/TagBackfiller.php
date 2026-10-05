<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Tags;

use Illuminate\Database\ConnectionInterface;
use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Rules\TagSlug;
use UnexpectedValueException;

/**
 * Idempotent, insert-only conversion of the legacy nest grouping into tags: each
 * nest yields the built-in game tag its name maps to (an {@see EggSpecificTags}
 * case) and, only when it grouped two or more eggs, a generic tag named after it,
 * with insertOrIgnore writes and read-back ids making repeated or partial runs safe.
 */
class TagBackfiller
{
    private const int CHUNK_SIZE = 500;

    /**
     * Tags to create, keyed by {@see cacheKey}.
     *
     * @var array<string, array{slug: string, name: string, legacy_nest_id: int|null}>
     */
    private array $pending = [];

    /**
     * Intended attachments as [cache key, egg id] pairs.
     *
     * @var array<int, array{0: string, 1: int}>
     */
    private array $attachments = [];

    public function __construct(private readonly ConnectionInterface $db, private readonly bool $dryRun = false) {}

    /**
     * Convert every nest into tags on its eggs.
     *
     * @return int the number of pivot rows written
     */
    public function backfill(): int
    {
        $this->plan();

        if ($this->dryRun) {
            return count(array_unique(array_map(
                fn (array $pair): string => $pair[0].':'.$pair[1],
                $this->attachments,
            )));
        }

        $this->createTags();

        return $this->attachTags($this->tagIdsBySlug());
    }

    /**
     * Work out every tag that needs to exist and every egg it needs to land on,
     * without touching the database beyond two reads.
     */
    private function plan(): void
    {
        $nests = $this->db->table('nests')->orderBy('id')->get(['id', 'name']);
        $eggsByNest = $this->db->table('eggs')->orderBy('id')->get(['id', 'nest_id'])->groupBy('nest_id');

        foreach ($nests as $nest) {
            $nestId = filter_var($nest->id, FILTER_VALIDATE_INT);
            throw_if($nestId === false || ! is_string($nest->name), UnexpectedValueException::class, 'The legacy nest query returned a malformed row.');

            $name = mb_trim($nest->name);
            $eggIds = [];
            foreach ($eggsByNest->get($nestId, collect())->pluck('id') as $eggId) {
                $parsedEggId = filter_var($eggId, FILTER_VALIDATE_INT);
                throw_if($parsedEggId === false, UnexpectedValueException::class, 'The legacy egg query returned a malformed identifier.');

                $eggIds[] = $parsedEggId;
            }

            if ($eggIds === []) {
                continue;
            }

            $special = EggSpecificTags::tryFromSlug($name);

            // The built-in game tag the nest names, so gating survives the cutover.
            if ($special instanceof EggSpecificTags) {
                $this->queue($special->value, $special->friendlyName(), null, $eggIds);
            }

            // A generic tag preserving the nest's grouping, but only if it grouped
            // more than one egg. A slug naming a built-in game folds into the
            // canonical tag above instead of duplicating it with different casing.
            if (count($eggIds) < 2 || $name === '' || TagSlug::isReserved($name)) {
                continue;
            }

            $slug = $special instanceof EggSpecificTags ? $special->value : $name;
            $label = $special instanceof EggSpecificTags ? $special->friendlyName() : $name;

            $this->queue($slug, $label, $nestId, $eggIds);
        }
    }

    /**
     * Queue a tag and its attachments. The first nest to claim a slug also supplies
     * its legacy nest id; a later nest folding onto the same slug does not overwrite
     * it, since only one nest can be the tag's origin.
     *
     * @param  list<int>  $eggIds
     */
    private function queue(string $slug, string $name, ?int $legacyNestId, array $eggIds): void
    {
        $key = $this->cacheKey($slug);

        if (! isset($this->pending[$key])) {
            $this->pending[$key] = ['slug' => $slug, 'name' => $name, 'legacy_nest_id' => $legacyNestId];
        } elseif (($this->pending[$key]['legacy_nest_id']) === null) {
            $this->pending[$key]['legacy_nest_id'] = $legacyNestId;
        }

        foreach ($eggIds as $eggId) {
            $this->attachments[] = [$key, $eggId];
        }
    }

    /**
     * Insert every planned tag, ignoring the ones that already exist. insertOrIgnore
     * carries the whole self-healing story: a slug the database already holds - by
     * an earlier run, a concurrent writer, or a collation equivalence the PHP-side
     * folding in {@see cacheKey} cannot predict, such as accent folding - is skipped
     * here and picked up by {@see tagIdsBySlug} instead.
     */
    private function createTags(): void
    {
        $now = now();

        $rows = array_map(fn (array $tag): array => [
            'name' => $tag['name'],
            'slug' => $tag['slug'],
            'legacy_nest_id' => $tag['legacy_nest_id'],
            'created_at' => $now,
            'updated_at' => $now,
        ], array_values($this->pending));

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            $this->db->table('tags')->insertOrIgnore($chunk);
        }
    }

    /**
     * Read back the id of every tag this run needs, keyed the way the planning pass
     * keyed its slugs. Always a database read: the ids are auto-increment, so they
     * cannot be known before the insert, and whichever row survived a collision is
     * the one the pivot rows must point at.
     *
     * @return array<string, int>
     */
    private function tagIdsBySlug(): array
    {
        $slugs = array_column($this->pending, 'slug');
        $ids = [];

        foreach (array_chunk($slugs, self::CHUNK_SIZE) as $chunk) {
            foreach ($this->db->table('tags')->whereIn('slug', $chunk)->get(['id', 'slug']) as $tag) {
                $id = filter_var($tag->id, FILTER_VALIDATE_INT);
                throw_if($id === false || ! is_string($tag->slug), UnexpectedValueException::class, 'The tag lookup returned a malformed row.');

                $ids[$this->cacheKey($tag->slug)] = $id;
            }
        }

        return $ids;
    }

    /**
     * Write the pivot rows, dropping duplicates and any attachment whose tag could
     * not be resolved.
     *
     * @param  array<string, int>  $tagIds
     * @return int the number of rows the database reported inserted
     */
    private function attachTags(array $tagIds): int
    {
        $rows = [];

        foreach ($this->attachments as [$key, $eggId]) {
            if (! isset($tagIds[$key])) {
                continue;
            }

            // De-duplicate before the insert rather than leaning on the primary key,
            // so a nest whose name folds onto its own game tag does not queue the
            // same row twice.
            $rows[$tagIds[$key].':'.$eggId] = [
                'tag_id' => $tagIds[$key],
                'kind' => 'egg',
                'taggable_type' => 'egg',
                'taggable_id' => $eggId,
            ];
        }

        $inserted = 0;

        foreach (array_chunk(array_values($rows), self::CHUNK_SIZE) as $chunk) {
            $inserted += $this->db->table('taggables')->insertOrIgnore($chunk);
        }

        return $inserted;
    }

    /**
     * Cache key for a slug, folded the way the `tags_slug_unique` index compares
     * values: the default collation is case-insensitive and ignores trailing spaces.
     * Insertion always keeps the verbatim slug; only lookups fold.
     */
    private function cacheKey(string $slug): string
    {
        return mb_strtolower(mb_rtrim($slug));
    }
}
