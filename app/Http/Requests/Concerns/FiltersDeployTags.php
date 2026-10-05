<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Collection;
use Pterodactyl\Models\Tag;
use Pterodactyl\Rules\TagSlug;
use Pterodactyl\Support\JsonValueGuard;

/**
 * Shared "deploy.tags" handling for the server store requests: tags may be given by
 * id or slug and are resolved to slugs from after() or later (never before
 * authorization), while a purely numeric value matching no tag is silently ignored as
 * a stale nest id left over in an un-updated billing module's configuration - a real
 * slug can never be purely numeric ({@see TagSlug}), so genuine typos still fail loudly.
 */
trait FiltersDeployTags
{
    /**
     * The tags this deploy resolved to, memoised so validation and the deployment
     * object share a single query.
     *
     * @var Collection<int, Tag>|null
     */
    private $resolvedDeployTags;

    /**
     * Fail validation when a deploy tag names neither an existing tag id nor an
     * existing slug, unless it is a legacy nest id left over in the caller's
     * configuration.
     */
    protected function validateDeployTagsExist(Validator $validator): void
    {
        $submitted = $this->deployTagInput();

        if ($submitted === []) {
            return;
        }

        $known = $this->matchedDeployTags()
            ->flatMap(fn (Tag $tag): array => [(string) $tag->id, $tag->slug])
            ->all();

        foreach (array_diff($submitted, $known) as $value) {
            if (TagSlug::isReserved($value)) {
                continue;
            }

            $validator->errors()->add('deploy.tags', "The node tag '{$value}' does not exist.");
        }
    }

    /**
     * Resolve the deploy tags, given as ids and/or slugs, to the slugs the
     * deployment gate matches on. Anything that matched no tag is already either a
     * dropped nest id or a validation error, so it simply does not appear here.
     *
     * @return string[]
     */
    protected function resolveDeployTagSlugs(): array
    {
        return JsonValueGuard::stringList($this->matchedDeployTags()->pluck('slug')->all());
    }

    /**
     * The tags named by deploy.tags, by id or slug, in one query.
     *
     * @return Collection<int, Tag>
     */
    private function matchedDeployTags()
    {
        if (($this->resolvedDeployTags) !== null) {
            return $this->resolvedDeployTags;
        }

        $submitted = $this->deployTagInput();

        return $this->resolvedDeployTags = Tag::matchingKeysOrSlugs($submitted);
    }

    /**
     * The deploy tags as submitted, normalised to a list of strings.
     *
     * @return list<string>
     */
    private function deployTagInput(): array
    {
        $input = $this->input('deploy.tags', []);
        if (! is_array($input)) {
            return [];
        }

        $tags = [];
        foreach ($input as $tag) {
            if (is_string($tag) && mb_trim($tag) !== '') {
                $tags[] = mb_trim($tag);
            } elseif (is_int($tag)) {
                // SAFETY: integer tag IDs have an exact decimal string representation.
                $tags[] = (string) $tag;
            }
        }

        return array_values(array_unique($tags));
    }
}
