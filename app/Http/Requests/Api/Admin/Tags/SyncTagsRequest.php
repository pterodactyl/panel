<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Tags;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Services\Tags\TagResolutionService;

/**
 * Replace the full set of tags on an egg or a node.
 *
 * Each value is either an existing tag id or a new tag name to create on the fly,
 * so a client can offer a combined "pick or type" field
 * ({@see TagResolutionService}).
 */
class SyncTagsRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminTagsUpdate];
    }

    /**
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'tags' => ['present', 'array'],
            // A numeric value is read as an existing tag id rather than a new name.
            // \Pterodactyl\Rules\TagSlug is what keeps those two spaces from
            // overlapping, so no rule is needed here beyond the length bound.
            'tags.*' => ['string', 'max:191'],
        ];
    }

    /**
     * The submitted values, normalised to strings for the resolver.
     *
     * @return list<string>
     */
    public function tagValues(): array
    {
        $values = $this->input('tags', []);
        if (! is_array($values)) {
            return [];
        }

        $tags = [];
        foreach ($values as $tag) {
            if (is_string($tag)) {
                $tags[] = $tag;
            } elseif (is_int($tag)) {
                // SAFETY: integer tag IDs have an exact decimal string representation.
                $tags[] = (string) $tag;
            }
        }

        return $tags;
    }
}
