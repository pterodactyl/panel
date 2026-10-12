<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Tags;

use Pterodactyl\Actions\Tags\SyncTags;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Models\Tag;

/**
 * Replace the full set of tags on an egg or a node.
 *
 * Each value is either an existing tag id or a new tag name to create on the fly,
 * so a client can offer a combined "pick or type" field
 * ({@see Tag::resolveIds()}).
 */
class SyncTagsRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminTagsUpdate];
    }

    /**
     * The action's rules, with every value held to its text rules: integer ids are numeric
     * strings by now (see prepareForValidation()).
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return [
            'tags' => SyncTags::rules()['tags'],
            'tags.*' => SyncTags::TEXT,
        ];
    }

    /**
     * The validated values, as strings for Tag::resolveIds().
     *
     * @return list<string>
     */
    public function tagValues(): array
    {
        $validated = $this->safe();

        return array_map(fn (int|string $key): string => $validated->string('tags.'.$key)->toString(), array_keys($validated->array('tags')));
    }

    /** Tag ids sent as JSON integers are read the same as the numeric strings the panel sends. */
    protected function prepareForValidation(): void
    {
        $tags = $this->input('tags');
        if (! is_array($tags)) {
            return;
        }

        foreach ($tags as $key => $tag) {
            if (is_int($tag)) {
                $tags[$key] = (string) $tag;
            }
        }

        $this->merge(['tags' => $tags]);
    }
}
