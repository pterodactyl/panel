<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Tags;

use Pterodactyl\Enum\EggSpecificTags;
use Pterodactyl\Models\Tag;
use Pterodactyl\Rules\TagSlug;

class TagResolutionService
{
    /**
     * Resolve a mixed list of tag ids and/or new tag names into tag ids, creating
     * any tag that does not exist yet. This is what lets the node and egg tag
     * pickers create a tag inline by typing a new name.
     *
     * Callers validate the submitted values first (SyncTagsRequest bounds each entry
     * to a string of at most 191 characters); combined with the numeric guard below
     * that satisfies every rule in TagRules::rules(), so tags created here need no
     * separate check.
     *
     * @param  string[]  $values
     * @return int[]
     */
    public function resolve(array $values): array
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
            // A purely numeric value is either an existing tag id or a leftover
            // legacy nest id - never a slug to create (see TagSlug). Resolve it if it
            // names a real tag, and drop it otherwise rather than materialising a
            // tag whose name is a stale nest id.
            if (TagSlug::isReserved($value)) {
                $existing = $byId->get($value)?->id;

                if (($existing) !== null) {
                    $ids[] = $existing;
                }

                continue;
            }

            // Treat it as a new tag name. The slug is the typed text verbatim, so a
            // name keeps the exact casing and spacing entered, and matches a
            // backfilled nest tag of the same name. Typing an exact built-in slug
            // still resolves to its canonical, enum-named tag.
            $name = EggSpecificTags::tryFrom($value)?->friendlyName() ?? $value;

            $ids[] = ($bySlug->get($value) ?? Tag::query()->firstOrCreate(['slug' => $value], ['name' => $name]))->id;
        }

        return array_values(array_unique($ids));
    }
}
